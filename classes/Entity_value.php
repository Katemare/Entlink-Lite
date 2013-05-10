<?
abstract class Entity_value extends Entity
{
	static $mod_formats=array(
		'values_html'=>'%value%',
		'value'=>array('func', 'displayValue'),
		'input_new'=>array('syn', 'values_html'),
		'input_edit'=>array('syn', 'values_html'),
		'input_value'=>'<input type=text name="%input_name%" value="%value[raw]%">',
		'input_name'=>array('func', 'input_name'),
		'dataerr'=>array('func', 'display_dataErr'),
		'nodata'=>'Нет данных!',
		'invalid'=>'Неверные данные!',
		'generic_dataerr'=>'Неверные данные!',
		'err_msg'=>'<span style="color:red">Ошибка</span>: '
	);
	
	public function setFormats()
	{
		parent::setFormats();
		$this->formats=array_merge($this->formats, self::$mod_formats);
	}
	
	// FIX: анализ контекста в этой функции какой-то кривой. Нужно более элегантное и интуитивно понятное решение.
	public function displayValue($args, $context)
	{
		$this->analyzeContext($context);
		$result='';		
		
		if ( (in_array('raw', $args, 1)) || ($context->display_values()) )
		// здесь есть возможность, что raw будет значением какого-нибудь именованного параметра. возможно, тут нужна какая-нибудь дополнительная функция по подготовке аргументов, как функция анализа контекста (которую, напротив, можно сильно сократить).
		{
			if (in_array('raw', $args, 1)) $err_report=true;
			else $err_report=false;
			
			if (!$this->metadata('ready'))
			{
				if ($err_report) $result.= $this->expandCode('dataerr', '', $context);
				$result.= $this->displayDefaultValue('', $context);
			}
			else $result= $this->displayVerifiedValue(null, $args, $context);
		}
		// STUB: нужно добавить проверку прав! хотя скорее всего это в родительскую сущность.
		elseif ($context->display_input())
		{
			$result=$this->expandCode('input_value', $args, $context);
			return $result;
		}
		return $result;
	}
	
	public function displayVerifiedValue($val=null, $args='', $context)
	{
		$this->analyzeContext($context);
		if (is_null($val)) $val=$this->getValue(null, false);
		
		return $this->format_safe($val, $context->format);
	}
	
	public function format_safe($val, $format)
	{
		if ($format=='html') $result=htmlspecialchars($val);
		elseif  ($format=='sql')
		{
			$result=mysql_real_escape_string($val);
		}
		return $result;
	}
	
	// STUB
	public function input_name()
	{
		return 'test';
	}
	
	public function displayDefaultValue($args='', $context)
	{
		$this->analyzeContext($context);
		$val=$this->model['default'];
		return $this->displayVerifiedValue($val, $args, $context);
	}
	
	public function display_dataErr($args, $context)
	{
		$this->analyzeContext($context);
		$meta=$this->metadata();
		if (!$meta['got_data']) return $this->expandCode('error', array('nodata'), $context);
		elseif (!$meta['valid']) return $this->expandCode('error', array('invalid'), $context);
		else return $this->expandCode('error', array('generic_dataerr'), $context);
	}
	
	// STUB: может быть написано изящнее.
	public function error($args, $context)
	{
		$this->analyzeContext($context);
		if ( ($context->display_values()) || ($context->display_input()) )
			$result=
				$this->expandCode('err_msg', '', $context) .
				$this->expandCode($args[0], '', $context);
		return $result;
	}
	
	// должно ли значение changed сохраняться, если сущность теряет данные? возможны ли такие ситуации? по смыслу это поле должно сообщать, необходимо ли обновлять значения в БД.
	public function analyzeData()
	{
		if ( (isset($this->metadata['checked'])) && ($this->metadata['checked']) ) return;
		static $nodata=array(
			'got_data'=>false,
			'valid'=>false,
			'source'=>null,
			'correctable'=>false,
			'changed'=>false,
			'safe'=>false,
			'securable'=>false,
			'normalized'=>false,
			'checked'=>true
		);		
		if (count($this->data)<1)
		{
			$metadata['got_data']=false;
		}
		elseif (is_null($this->metadata['source']))
		{
			$metadata['got_data']=false;
		}
		else
		{
			$metadata=array(
				'got_data'=>null,
				'valid'=>null,
				'source'=>null,
				'correctable'=>null,
				'changed'=>null,
				'safe'=>null,
				'securable'=>null,
				'normalized'=>null
			);
			
			$this->analyzePresentData($metadata);
		}
		
		if ($metadata['got_data']===false) $metadata=$nodata;
		else $metadata['source']=$this->metadata['source'];
		$metadata['ready']= $metadata['got_data'] && $metadata['safe'] && ($metadata['valid']);
		$metadata['checked']=true;
		$this->metadata=$metadata;
	}
	
	public function analyzePresentData(&$metadata)
	{
		if (!array_key_exists('value', $this->data)) $metadata['got_data']=false;
		elseif (is_null($this->data['value'])) $metadata['got_data']=false;
		else
		{
			$metadata['got_data']=true;
		
			if ($this->autonormal)
			{
				$this->data['value']=$this->normalizeData($this->data['value']);
				$metadata['normalized']=true;
			}
			else $metadata['normalized']=false;
			
			$metadata['valid']=$this->isValid($this->data['value']);
			if ($metadata['valid']===false)
			{
				$valid=$this->correctData($this->data['value']);
				$metadata['correctable']=!is_null($valid);
				if ( ($metadata['correctable']===true) && ($this->autocorrect))
				{
					$metadata['valid']=true;
					$metadata['correctable']=null;
					$this->data['value']=$valid;
					$this->data['changed']=true;
				}
			}
			
			$metadata['safe']=$this->isSafe($this->data['value']);
			if ($metadata['safe']===false)
			{
				$safe=$this->secureData($this->data['value']);
				$metadata['securable']=!is_null($safe);
				if ( ($metadata['securable']===true) && ($this->autosecure))
				{
					$metadata['safe']=true;
					$metadata['securable']=null;
					$this->data['value']=$safe;
					$this->data['changed']=true;
				}
			}
		}
	}
	
	// inherit us!
	public function normalizeData($val)
	{
		return $val;
	}
	
	public function correctData($val)
	{
		$val=$this->correctData_produce($val);
		if (!isValid($val)) return null;
		else return $val;
	}
	public function correctData_produce($val)
	{
		return $val;
	}
	
	public function secureData($val)
	{
		$val=$this->secureData_produce($val);
		if (!isSafe($val)) return null;
		else return $val;
	}
	public function secureData_produce($val)
	{
		return $val;
	}
	
	public function safe_source($source)
	{
		static $safe_sources=array('DB', 'default', 'updated');
		return in_array($source, $safe_sources, 1);
	}
	
	// inherit us!
	public function isValid($val)
	{
		return true;
	}
	public function isSafe($val)
	{
		return true;
	}
}
?>