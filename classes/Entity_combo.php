<?
class Entity_combo extends Entity
{
	public $model=array();
	
	static $mod_model=array();
	
	public function __construct()
	{
		parent::__construct();
		$this->setModel();
	}
	
	public function setModel()
	{
		if (count(self::$mod_model)) $this->mergeModel(self::$mod_model);
	}
	
	public function mergeModel($tomerge)
	{
		foreach ($tomerge as $key=>$params)
		{
			if (!array_key_exists($key, $this->model)) $this->model[$key]=$params;
			else
			{
				foreach ($params as $param=>$val)
				{
					$this->model[$key][$param]=$val;
				}
			}
		}
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
				
		if ( (is_null($metadata['valid'])) && (!$member->metadata('valid') )
		{
			$metadata['valid']=false;
		}
		if ( (is_null($metadata['correctable'])) && ($metadata['valid']===false) && (!$member->metadata('correctable') ) )
		{
			$metadata['correctable']=false;
		}
		if ( (is_null($metadata['safe'])) && (!$member->metadata('safe') )
		{
			$metadata['safe']=false;
		}
		if ( (is_null($metadata['securable'])) && ($metadata['safe']===false) && (!$member->metadata('securable') ) )
		{
			$metadata['securable']=false;
		}
		if ( (is_null($metadata['normalized'])) && (!$member->metadata('normalized')) )
		{
			$metadata['normalized']=false;
		}
		if (is_null($metadata['source'])) $metadata['source']=$member->metadata('source');
		elseif (($metadata['source']<>'mixed') && ($metadata['source']<>$member->metadata('source')) ) $metadata['source']='mixed';
		if ( (is_null($metadata['changed'])) && ($member->metadata('changed'))) $metadata['changed']=true;	
	}	
}
?>