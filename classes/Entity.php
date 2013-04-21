<?
abstract class Entity
{
	// в этой переменной хранятся все созданные объекты-сущности по их уникальным идентификаторам, взятым из БД. здесь нет сущностей, у которых ещё нет уникального идентификатора, потому что они ещё не сохранены или не заполнены, к примеру!
	static $entities_by_uni=array();
	
	public $type=null; // по сути то же самое, что стоит в названии класса после Entity_

	public $data=array(); // пока не ясно, будет ли использоваться этот массив сколько-нибудь сложными сущностями. возможно, для показа неотображаемых данных.
	
	// данные о данных - допустимы ли они, изменились ли, получены ли и откуда...
	// в том числе очень важный массив в ключе model.
	public $metadata=array();
	// got_data = true, false. Получены ли данные? Для комбо (здесь и ниже) - все данные.
	// checked = true, false. Были ли данные проверены?
	// valid = true, false. Действительны ли данные? Не введено ли в поле, предназначенное для числа от 1 до 100, значения "0" или "вася"? Для комбо также учитывает правильное сочетание данных (например, сумма статов не больше Х).
	// correctable = true, false. Если данные не действительны, могут ли они быть откорректированы (хотя бы заменены на значения по умолчанию)?
	// corrected = true, false. Были ли данные откорректированы?
	// safe = true, false. Безопасны ли данные? Подразумевается отсутствие символов, которых не может в них быть по смыслу, например, не употребляющихся при вводе. Опасные символы, такие как апострофы для БД или угловые скобки для html, должны устраняться на месте выводав соответствующий формат, без изменений строки.
	// securable = true, false. Могут ли быть данные исправлены для восстановления безопасности?
	// secured = true, false. Были ли данные исправлены ради безопасности.
	// normalized = true, false. Приведены ли данные в стандартную форму? Например, перенос строки приравнен к линуксовому стандарту.
	// source = 'default', 'DB', 'input', 'updated', 'mixed' (combo only)
	// changed = true, false. Были ли данные исправлены программой после получения.
	// ready = got_data && safe && valid
	public $autosafe=true, $autocorrect=false, $autonormal=true;
	// эти переменные устанавливают, следует ли объекту автоматичесски исправлять данные.

	public $uni=0;
	// уникальный идентификатор сущности, по которому она известна в БД. только для сущностей, сохранённых в БД.
	
	// устанавливает уникальный иденифтикатор и вписывает сущность в общий массив.
	public function setUni($uni)
	{
		$uni=(int)$uni;
		$olduni=$this->uni;
		if ($olduni>0) unset(Entity::$entities_by_uni[$olduni]);
		$this->uni=$uni;
		if ($uni>0) Entity::$entities_by_uni[$uni]=$this;
		// ERR: нет обработки ошибки
	}
	
	// эта функция вызывается, когда Ретривер получил запрошеные данные.
	// FIX: она должна быть переписана!
	public function receive($tables, $context, $args)
	{
		debug('received '.$this->id);
		if ((!$this->metadata('typed'))&&(in_array('entities', $tables)))
		{
			$result=EntityFactory::build_by_Retriever($this);
			if (!$result) { } // ERR - not found
		}
		// elseif (!$this->metadata('typed)) { } //ERR
		$this->prepare_storage();
		$this->storage->receive($tables, $context, $args);
	}
	
	// именно через эту функцию нужно обращаться к метаданным. Это позволяет проверить легальность содержимого по запросу, а не сразу, когда данные ещё могут быть не цельными.
	public function metadata($code='')
	// $code указывает на элемент массива метаданных, который нужно получить. если не указана, функция возвращает все метаданные.
	{
		$this->analyzeData();
		if ($code=='') return $this->metadata;
		elseif (array_key_exists($code, $this->metadata)) return $this->metadata[$code];
		else return false;
	}
	
	// эта функция проверяет легальность и исправляемость данных. 
	// STUB: функция ещё не написана!
	public abstract function analyzeData();
	
	// эта функция достаёт данные из массива данных. по умолчанию - только готовые, валидные данные.
	public function getValue($code='value', $readied=true)
	{
		if (($readied)&&(!$this->metadata('ready'))) return null;
		if (array_key_exists($code, $this->data)) return $this->data[$code];
		return null;
	}
	
	// эта функция устанавливает хранящиеся в сущности данные.
	
	// $value - значение, которое надо установить.
	// по умолчанию записывается в ключ массива 'value'. Если само $value представляет из себя массив, то данные записываются в соответствующие ключи.
	
	// $source - источник данных. допустимые источники данных:
	// 'default' - значения по умолчанию. не проверяются на валидность и безопасность, так как уже были проверены создателем модуля или средствами редактирования модуля.
	// 'DB' - из базы данных. не проверяется на валидность и безопасность, так как уже были проверены при добавлении в БД.
	// 'update' - изменение данных в процессе прогона программы. не сохраняется в метаданные как источник, но видно по changed.
	// 'input' - пользовательский ввод. проверяется на валидность и безопасность.
	// null - стирает данные.
	
	// $rewrite - переписывать ли данные, если они уже есть.
	public function setValue($value, $source, $rewrite=true)
	{
		if (is_null($source))
		{
			// FIX! стирание данных должно проходить иначе.
			$this->metadata['got_data']=false;
			setSource($source);
		}
		
		if (!is_array($value)) $set=array('value'=>$value);
		else $set=$value;
		
		// проверяем, были ли данные изменены по сравнению с БД. 
		// FIX: эта часть должна быть написана иначе!
		$changed=false;
		if ( ($source=='update') || (($source=='input')&&(!$this->metadata['changed'])) )
		{
			foreach ($set as $key=>$val)
			{
				if ($this->data[$key]!==$val)
				{
					$changed=1;
					break;
				}
			}
			
			if (($rewrite)&&(!$changed))
			{
				$diff=array_diff(array_keys($this->data), array_keys($set));
				if (count($diff)>0) $changed=1;
			}
		}
		else $changed=$this->metadata['changed'];
		
		
		if (($rewrite)&&($changed)) $this->data=$set;
		elseif ((!$rewrite)&&($changed)) $this->data=array_merge($this->data, $set);
		
		$this->metadata['changed']=$changed;
		$this->setSource($source);
	}
	
	// эта функция устанавливает источник данных и делает все необходимые при этом операции.
	public function setSource($source)
	{
		if ($source=='update') return; // если программа просто обновляет сведения самостоятельно, к примеру, корректирует чрезмерный пользовательский ввод, источник не меняется.
		
		$this->metadata['source']=$source;
		// хотя некоторые источники данных не нуждаются в проверке, это функции проверки решит сама. чтобы все правила были там, а не раскиданы по коду.
		$this->metadata['checked']=false;
		$this->metadata['valid']=false;
		$this->metadata['safe']=false;
	}

	public abstract function analyzeData();

	
	// конструирование сущности связано как с отображением, так и с хранением.
	public function __construct()
	{
		$this->setFormats();
	}	
	
	###############
	### display ###
	###############
	
	// форматы отображения, свойственные конкретно этому классу, а не его предкам.
	// FIX
	static $def_formats=array(
		'display'=>'%error%',
		'error'=>array('self', 'error')
	);

	// форматы отображения, складывающиеся из наследования и, возможно, обработки модулями.
	// FIX: возможно, следует просчитывать это ещё в статике, чтобы не для каждого экземпляра по разу?
	public $formats=array();
	
	public function SetFormats()
	{
		$this->formats=self::$def_formats;
	}		
	
	// эта основная функция, вызывающая отображение.
	// $context - объект класса Context, который курирует отображение как цельный процесс.
	// в первую очередь он хранит стадию отображения. Сначала нужно собрать все данные, потом - отображать целиком, так экономятся запросы.
	// $args - параметры, которые надо учесть при отображении (массив).
	public function display($args='', $context=null)
	{
		// if (is_array($args)) $args=$this->mergeArgs($args);
		//return $this->expandFormat('%display'.(($args<>'')?('['.$args.']'):('')).'%', $context);
		return $this->expandCode('display', $args, $context);
	}
	
	// поскольку данный код не использует возможности "closures" явно передавать аргументы в функцию по callback, то контекст приходится выуживать вот так...
	public function analyzeContext(&$context)
	{
		if (is_null($context)) $context=$this->context;
		elseif ($context!==$this->context) $this->context=$context;	
	}
	
	// эта функция расшифровывает текстовый формат, в котором хранится то, как должны отображаться разные компоненты сущности.
	public function expandFormat($format, $context=null)
	{
		$this->analyzeContext($context);
		debug('xFormat: '.htmlspecialchars($format));
		$result=preg_replace_callback(
			'/%(?<code>[\<\>]?[a-z_0-9]+)(\[(?<args>[^\]]+)\])?%/i', // формат имеет вид текста, где следующий код подставляется сущностью: %code[arg1;arg2=val]%
			array($this, 'expandCode_callback'),
			$format
			);
		// кстати, именно при этом вызове теряется связь с контекстом.
		return $result;		
	}
	
	// эта функция разбирает найденные аргументы отображения на частички и передаёт уже виде нормальных аргументов в другую функцию.
	public function expandCode_callback($m)
	{
		debug('xCode cb'.$this->id.': '.$m[0].' ('.$m['code'].')');
		
		$args=$this->parseArgs($m['args']);
		$code=$m['code'];
		
		// в двух элементах массива сохраняется строковый список аргументов и весь код целиком, что-то вроде кэша.
		if (!array_key_exists('default', $args)) $args['default']=$m[0];
		if (!array_key_exists('original', $args)) $args['original']=$m['args'];
		
		return $this->expandCode($code, $args);
	}	
	
	// итак, сущность выполняет отображение, принимая формат вида %код[аргумент1; аргумент2 = значение]%. Аргументы при этом не обязательны.
	// данная сущность интерпретирует уже разобранный формат.
	public function expandCode($code, $args='', $context=null)
	{
		$this->analyzeContext($context);
		debug('xCode '.$this->id.': '.$code);
		
		$result='';
		// обращение к связанной сущности. Начинается с > (дочерняя) или < (родительская).
		// FIX: возможно, должно быть написано иначе, потому что сущности составляют не древесную иерархию, а сеть.
		if (in_array($code[0], array('>', '<')))
		{
			debug ('xCode member');
			$member_code=substr($code, 1); // код сущности в модели.
			if ($context->do_req()) // этап сбора информации.
			{
				debug('req_member: '.$member_code);
				$this->req_member($member_code, $context, $args); // запросить информацию, необходимую для показа сущности в таком виде.
				// впоследствии этот запрос передаётся сущности, а она уже разбирается, какая информация ей нужна. Если ей нужна ещё одна стадия опроса, это делается автоматически после запуска метода received.
				// этот запрос не учитывает дополнительный код, в котором может понадобиться связанная сущность. Такой код должен раскрываться одной из ветвей ниже, и если в нём есть упоминание сырого объекта - то передаётся запрос display с соответствущими аргументами. однако это применение не позволяет запрость данные сущности помимо тех, которые связаны с показом... Что вполне нормально для редактирования (редактируются только те данные, у которых были поля в форме), но для некоторых других применений этого может быть мало. 
				// с другой стороны, одна сущность не должна слишком лезть в особенности другой, так что может быть передача простой команды "покажись, вот контекст и аргументы" достаточно.
				$result='REQ_MEMBER: '.$member_code; //$args['default'];
			}
			elseif ($context->display_values()) // этап показа данных.
			{
				$member=$this->data[$member_code];
				if ($member instanceof Entity) $result=$member->display($args, $context); // она показывается.
				//else $result=$this->expandFormat('%error[no_member;ask='.$link.']%');
				else $result=$this->expandCode('error', array('ask'=>$member_code), $context);
			}
		}
		elseif (array_key_exists($code, $this->formats)) // если для данного кода задан формат.
		{
			if (is_string($this->formats[$code])) // формат может быть строковый, тогда разбираем его как строку. в таком случае аргументы не используются.
			{
				$result=$this->expandFormat($this->formats[$code]);
			}
			elseif (is_array($this->formats[$code])) // или формат может представлять из себя указание на объект и метод, которому нужно переадресовать отображение. это делается для случаев, когда отображение требует логики.
			// первый аргумент - кого опросить, второй - название функции. первый аргумент сейчас использует только значение 'self'.
			{
				$obj=$this; // пока что можно обращаться только с ебе.
				
				//if ($result=='')
				{
					$method=$this->formats[$code][1];
					//if ($method=='expandCode') $result= $this->expandFormat($obj->expandCode($code, $args, $context)); // 
					//else $result=$this->expandFormat($obj->$method($args, $context));
					$result=$obj->$method($args, $context);
				}
			}
			
/*			elseif ($this->formats[$code] instanceof Entity)
			{
				$entity=$formats[$code];
				return $entity->expandCode($code, $args, $context);
			}
*/			
		}
		else // не найдено способа обработать код.
		{
			$result= $args['default']; // считаем, что это и не код вовсе.
			// надо сказать, что при использовании функции display этот элемент массива пуст, потому что обращение идёт сразу к данной функции, без первоначального формата.
		}
		// else ERR
		
		return $result;
	}
	
	// конвертирует текстовую запись аргументов в массив.
	public function parseArgs($s='')
	{
		if ($s=='') return array();
		$list=explode(';', $s);
		$result=array();
		foreach ($list as $arg)
		{
			if ($arg[0]==='#') $result['uni']=substr($arg, 1);
			if (preg_match('/^([a-z\_0-9]+)=(.+)$/i', $arg, $m))
			{
				if (strpos($m[2], ',')!==false) $m[2]=explode(',', $m[2]);
				$result[$m[1]]=$m[2];
			}
			else $result[]=$arg;
		}
		$result['original']=$s; // 
		return $result;
	}
	
	// конвертирует массив аргументов в их строковую запись.
	public function mergeArgs(&$source='')
	{
		if (($source=='')||(count($source)==0)) return '';
		if (array_key_exists('original', $source)) return $source['original']; // этот элемент служит кэшем строкового вида аргументов.
		$s=$source;
		
		// эти элементы возникают только во время существования аргументов как массив, в строковую запись их не нужно.
		unset($s['original'], $s['default']); 
		
		$result=array();
		foreach ($s as $key=>$value)
		{
			if (is_numeric($key)) $result[]=$value;
			elseif (is_array($value)) $result[]=$key.'='.implode(',', $value);
			else $result[]=$key.'='.$value;
		}
		$result=implode(';', $result);
		$source['original']=$result;
		return $result;
	}
		
	public function error($args, $context)
	{
		$this->analyzeContext();
		return $args[0];
	}	
}

function debug($msg)
{
	echo $msg.'<br>';
}
?>