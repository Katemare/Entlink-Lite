<?
class Entity_title extends Entity_string
{
	public $maxlength=200, $invalid_ex='\x0-\x1F';

	public function analyzePresentData(&$metadata)
	{
		parent::analyzePresentData($metadata);
		$metadata['valid']=
			strlen($this->data['value'])<=$this->maxlength &&
			strlen($this->data['value'])>0 &&
			!preg_match('/['.$this->invalid_ex.']/', $this->data['value']);
		if ($metadata['valid']===false) $metadata['correctable']=$this->correctData($metadata, false);
	}
	
	public function correctData(&$metadata, $apply=true)
	{
		$result=
	}
}
?>