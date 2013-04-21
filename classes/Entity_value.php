<?
abstract class Entity_value extends Entity
{
	static $mod_formats=array(
		'display'=>'%value%',
		'value'=>array('self', 'displayValue'),
		'dataerr'=>array('self', 'display_dataErr'),
		'nodata'=>'Нет данных!',
		'invalid'=>'Неверные данные!',
		'generic_dataerr'=>'Неверные данные!',
		'err_msg'=>'<span style="color:red">Ошибка</span>: '
	);
	
	// replace me!
	public function displayValue($args, $context)
	{
		$this-analyzeContext($context);
		$result='';
		if (!$this->metadata('ready'))
		{
			$result.= $this->expandCode('dataerr', '', $context);
			$result.= $this->displayDefaultValue('', $context);
		}
		else $result= displayVerifiedValue(null, $args, $context);
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
		if ($format=='html') $result= htmlspecialchars($val);
		elseif  ($format=='sql')
		{
			$result=mysql_real_escape_string($val);
		}
		return $result;
	}
	
	public function displayDefaultValue($args='', $context)
	{
		$this->analyzeContext($context);
		$val=$this->getValue('default', false);
		return $this->displayVerifiedValue($val, $args, $context);
	}
	
	public function display_dataErr($args, $context)
	{
		$this-analyzeContext($context);
		$meta=$this->metadata();
		if (!$meta['got_data']) return $this->expandCode('error', array('nodata'), $context);
		elseif (!$meta['valid']) return $this->expandCode('error', array('invalid'), $context);
		else return $this->expandCode('error', array('generic_dataerr'), $context);
	}
	
	// STUB: может быть написано изящнее.
	public function error($args, $context)
	{
		$this->analyzeContext();
		if ( ($context->display_values()) || ($context->display_input()) )
			return
				$this->expandCode('err_msg', '', $context) .
				$this->expandCode($args[0], '', $context);
	}
	
	// должно ли значение changed сохраняться, если сущность теряет данные? возможны ли такие ситуации? по смыслу это поле должно сообщать, необходимо ли обновлять значения в БД.
	public function analyzeData()
	{
		if ($this->metadata['checked']) return;
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
		elseif (is_null($metaadta['source']))
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
			
			if ( ($metadata['normalized']===false) && ($this->autonormal)) $this->normalizeData($metadata);
			if ( ($metadata['valid']===false) && ($metadata['correctable']===true) && ($this->autocorrect)) $this->correctData($metadata);
			if ($metadata['valid']===false) $metadata['safe']=false;
			if ( ($metadata['safe']===false) && ($metadata['securable']===true) && ($this->autosecure)) $this->secureData($metadata);
		}
		
		if ($metadata['got_data']===false) $metadata=$nodata;
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
			
			$metadata['correct']=$this->isCorrect($this->data['value']);
			if ($metadata['correct']===false)
			{
				$metadata['correctable']=$this->correctData($this->data['value'], false);
				if ( ($metadata['correctable']==true) && ($this->autocorrect))
				{
					$this->data['value']=$this->correctData($this->data['value']);
					$this->data['changed']=true;
				}
			}
			
			$metadata['safe']=$this->isSafe($this->data['value']);
			if ($metadata['safe']===false)
			{
				$metadata['securable']=$this->secureData($this->data['value'], false);
				if ( ($metadata['securable']==true) && ($this->autosecure))
				{
					$this->data['value']=$this->secureData($this->data['value']);
					$this->data['changed']=true;
				}
			}
		}
	}
	
	// inherit me!
	public function normalizeData($val)
	{
		return $val;
	}
	
	public function correctData($val, $doit=true)
	{
		if (!is_null($this->data['corrected'])) $result= $this->data['corrected'];
		else
		{
			$result=$this->correctData_produce($val);
			$this->data['corrected']=$result;
		}
		if ($doit)
		{
			$this->data['value']=$result;
			return $
		return $result;
	}
	// inherit me!	
	public function correctData_produce($val)
	{
		return $val;
	}
	
	public function secureData($val, $doit=true)
	{
		if (is_null($metadata)) $meta=$this->metadata;
		else $meta=$metadata;
		
		//if  ($metadata['securable']!==true) return false;
		
		$meta['secured']=true;
		
		if (is_null($metadata)) $this->metadata=$meta;
		else $metadata=$meta;
	} 
	
	public function safe_source($source)
	{
		static $safe_sources=array('DB', 'default', 'updated');
		return in_array($source, $safe_sources, 1);
	}
	
	// inherit me!
	public functon isValid($val)
	{
		return true;
	}	
}
?>