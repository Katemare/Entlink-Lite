<?
class Entity_combo extends Entity
{
	static $mod_formats=array(
		'form_new'=>'<form action="%form_action%" method="%form_method%"><input type=hidden name=action value="new">%parse[mode=input_new]%<br><input type=submit value="%submit_value%"></form>',
		'form_edit'=>'<form action="%form_action%" method="%form_method%"><input type=hidden name=action value="edit"><input type=hidden name=uni value="%uni%">%parse[mode=input_edit]%<br><input type=submit value="%submit_value%"></form>',
		'input_new'=>array('syn', 'values_html'),
		'input_edit'=>array('syn', 'values_html'),
		'form_method'=>'GET',
		'form_action'=>'',
		'submit_value'=>'Отправить...'
	);

	public function setFormats()
	{
		parent::setFormats();
		$this->formats=array_merge($this->formats, self::$mod_formats);
	}

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
			
			$this->analyzeData_iterate($metadata);
			
			if (is_null($metadata['got_data'])) $metadata['got_data']=true;
			if ($metadata['got_data'])
			{
			
				if (is_null($metadata['valid'])) $metadata['valid']=true;
				elseif (!$metadata['valid']) { $metadata['safe']=false; $metadata['securable']=false; }
				if ( (!$metadata['valid'])&&(is_null($metadata['correctable'])) ) $metadata['correctable']=true;
				
				if (is_null($metadata['safe'])) $metadata['safe']=true;
				if ( (!$metadata['safe'])&&(is_null($metadata['securable'])) ) $metadata['securable']=true;
				
				if  (is_null($metadata['normalized'])) $metadata['normalized']=true;
				
				if (is_null($metadata['changed'])) $metadata['changed']=false;
			}
			$metadata['checked']=true;			
		}
		if (!$metadata['got_data']) $metadata=$nodata;
		$metadata['ready']=$metadata['got_data'] && $metadata['safe'] && $metadata['valid'];
		$this->metadata=$metadata;
	}
	
	public function analyzeData_iterate(&$metadata)
	{
		foreach ($this->model as $key=>$params)
		{
			// $member=$this->getValue($key, false); // проверки на готовность не требуется, потому что мы прямо сейчас её совершаем.
			$member=$this->data[$key];
			if (! $member instanceof Entity)
			{
				if ( (!$params['optional']) &&  (is_null($metadata['got_data'])) )
				{
					$metadata['got_data']=false;
					$metadata['valid']=false;
				}
				continue;
			}
			$this->analyzeData_member($metadata, $member);
		}
		// не проверяет, есть ли в данных поля, не упомянутые в модели. Считается, что такая ситуация не может случиться.
	}
	
	public function analyzeData_member(&$metadata, $member)
	{
		$member->analyzeData();

		if ( (is_null($metadata['normalized'])) && (!$member->metadata('normalized')) )
		{
			$metadata['normalized']=false;
		}
				
		if ( (is_null($metadata['valid'])) && (!$member->metadata('valid')) )
		{
			$metadata['valid']=false;
		}
		
		if ( (is_null($metadata['correctable'])) && ($metadata['valid']===false) && (!$member->metadata('correctable') ) )
		{
			$metadata['correctable']=false;
		}
		
		if ( (is_null($metadata['safe'])) && (!$member->metadata('safe')) )
		{
			$metadata['safe']=false;
		}
		if ( (is_null($metadata['securable'])) && ($metadata['safe']===false) && (!$member->metadata('securable') ) )
		{
			$metadata['securable']=false;
		}
		
		if (is_null($metadata['source'])) $metadata['source']=$member->metadata('source');
		elseif (($metadata['source']<>'mixed') && ($metadata['source']<>$member->metadata('source')) ) $metadata['source']='mixed';
		if ( (is_null($metadata['changed'])) && ($member->metadata('changed'))) $metadata['changed']=true;	
	}	
}
?>