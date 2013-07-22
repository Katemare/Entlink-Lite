<?
// этот класс должен собирать запросы на данные и выполнять их скопом по мере надобности, чтобы уменьшить число запросов к БД. например, если странице нужны данные 20 неофитов с известными идентификаторами, то вместо 20 запросов "неофит с таким-то идентификатором" этот класс должен выполнить запрос "неофиты с идентификаторами 1, 2, 3...". далее объекты неофитов сами разбирают данные.
// следовательно, нас есть следующие этапы:
// 1. объекты говорят ретриверу, что им понадобятся такие-то данные. ретривер запоминает.
// 2. объект говорит ретриверу: не могу больше терпеть! данные нужны сейчас! рертривер выполняет запрос и получает как можно больше данных с помощью одного запроса.
// 3. объект, потребовавший данные, берёт их из ретривера. когда подходит очередь срочной нужды в данных других объектов, то они делают то же (потому что данные уже были получены).
// кроме того, ретривер должен грамотно получать сопутствующие (связанные) данные. к примеру, атаки неофита, хотя они хранятся в другой таблице, комментарии... причём он должен различать, когда комментарии нужны (при показе страницы), а когда - нет (при скрытых операциях с неофитом).

class EntityRetriever extends EntityDBOperator
{
	const MAX_CYCLE=10;
	public static $calls=array();

	public static $queries=array();
	// в этом массиве хранятся пары "таблица => список идентификаторов". это очередь на получение данных.
	public static $queries_by_conditions=array();
	// аналогично, но вместо списка идентификаторов - массив условий.
	
	public static $data=array();
	// здесь хранятся данные. ретривер не стирает их до самого конца прогона программы, чтобы не запоминать, сколько объектов запросили данные и когда они уже не понадобятся. в любом случае копии массивов в php хранятся как один экземпляр в памяти, пока не будет внесено изменение.
		
	public static function received($queue_data)
	{
	// ERR

		$method='receive';
		$ask='';
		if ($queue_data['method']<>'') $method=$queue_data['method'];
		if ($queue_data['ask']<>'') $ask=$queue_data['ask'];
		$entity=$queue_data['entity'];	
		
		debug('clearing queue: '.$entity->id.'->'.$ask.'->'.$method);
		
		if ($ask=='') $entity->$method($queue_data['tables'], $queue_data['context'], $queue_data['args']);
		else $entity->$ask->$method($queue_data['tables'], $queue_data['context'], $queue_data['args']);
	}
	
	public static function makeCall($call)
	{
		if ($call instanceof Entity) $call->receive();
		elseif (is_array($call))
		{
			debug('xCall '.$call['target']->id.' '); var_dump($call['args']);
			$target=$call['target'];
			if (array_key_exists('method', $call)) $method=$call['method'];
			else $method='receive';
			if (array_key_exists('args', $call)) $args=$call['args'];
			else $args=null;
			
			if (is_null($args)) $target->$method();
			else $target->$method($args);
		}
	}
	
	public static function req_by_condition($conditions, $table, $call)
	{
		foreach ($conditions as $key=>$condition)
		{
			
		}
	}
	
	public static function retrieve_by_conditions($table=null)
	{
		if (is_null($table))
		{
			foreach (static::$queries_by_conditions as $table=>$conditions)
			{
				static::retrieve_by_conditions($table);
			} 
		}
		else
		{
			$conditions=static::$queries_by_conditions[$table];
			$tomerge=array();
			foreach ($conditions as $key=>$condset)
			{
				$fields_hash=array();
				foreach ($condset as $key2=>$cond)
				{
					if (is_numeric($key2)) $fields_hash[]=$cond;
					else $fields_hash[]=$key2;
				}
				sort($fields_hash);
				$fields_hash=implode(',', $fields_hash);
				if (isset($tomerge[$fields_hash])) $tomerge[$fields_hash][]=$key;
				else $tomerge[$fields_hash]=array($key);
			}
			
			foreach ($tomerge as $keys)
			{
				if (count($keys)==1) continue;
				$field_vals=array();
				foreach ($keys as $key)
				{
					$condset=$conditions[$key];
					foreach ($condset as $field=>$val)
					{
						if (is_numeric($field)) continue;
						if (!isset($field_vals[$field])) $field_vals[$field]=array();
						$field_vals[]=$val;
					}
				}
				$field_counts=array();
				foreach ($field_vals as $field=>$vals)
				{
					$field_vals[$field]=array_unique($vals);
					$field_counts[$field]=count($field_vals[$field]);
				}
				asort($field_counts);
				
			}
		}
	}
	
	public static function req($id, $table='entities', $call=null)
	{
		if (is_array($table))
		{
			$result=array();
			$table=array_unique($table);
			foreach ($table as $t)
			{
				// $result[$t]=static::req($t, $id, $call);
				static::req($t, $id, $call);
			}
			return $result;
		}
		elseif (is_array($id))
		{
			$result=array();
			$id=array_unique($id);
			foreach ($id as $i)
			{
				// $result[$i]=static::req($table, $i, $call);
				static::req($table, $i, $call);
			}
			return $result;
		}
		else
		{
			if (!is_numeric($id))
			{
				// эта функция преобразует некоторые строковые идентификаторы в уникальный идентификатор. Это нужно в случае, если мы не знаем точно уникальный идентификатор, но знаем, какая функционально сущность требуется.
				$id=static::unize($id);
			}
			if (
				(is_numeric($id)) &&
				(isset(static::$data[$table])) &&
				(array_key_exists($id, static::$data[$table]))
				)
			{
				// запрашиваемые данные уже получены.
				if (!is_null($call)) static::makeCall($call);
				static::$data[$table][$id];
				return;
			}
			
			// данные ещё не получены.
			static::$queries[$table][]=$id;
			if (!is_null($call)) static::addCall($call);
		}
	}
	
	public static function retrieve($cycle=0) // этой командой объект запрашивает своё содержимое массива $data в форме, совместимой с функцией do_input. эта команда значит "данные нужны сейчас!".
	{
		if ($cycle==0)
		{
			debug ('retrieving all...');
			while (count(static::$queries)>0)
			{
				$cycle++;
				if ($cycle>static::MAX_CYCLE)
				{
					debug ('MAX CYCLE!');
					break;
				}
				static::retrieve($cycle);
			}
			return;
		}
		
		debug ('retrieving...');
		$db=parent::$db;
		foreach (static::$queries as $table=>$ids)
		{	
			$ids=array_unique($ids);
			$query=array(
				'action'=>'select',
				'table'=>static::$db_prefix.$table,
				'where'=>array('uniID'=>$ids),
			);
			
			$query=static::compose_query($query);
			$list=$db::query($query);
			
			while ($row=$db::fetch($list))
			{
				static::$data[$table][$row['uniID']]=$row;
			}
			
			unset(static::$queries[$table]);
		}
		
		debug ('clearing calls queue: '.count(static::$calls));
		// static::$calls=array_unique(static::$calls);
		// $clear=array();
		foreach (static::$calls as $call)
		{
			static::makeCall($call);
		}
	}
	
	public static function addCall($call)
	{
		static::$calls[]=$call;
	}
}
?>