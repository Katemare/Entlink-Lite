<?

class Entity_string extends Entity_value
{
	public function analyzePresentData(&$metadata)
	{
		if (!array_key_exists('value', $this->data)) $metadata['got_data']=false;
		elseif (is_null($this->data['value'])) $metadata['got_data']=false;
		else $metadata['got_data']=true;
		
		$metadata['safe']=true; // при условии, что значение будет обработано по формату (format_safe), оно не может не быть безопасным.
		$metadata['correct']=true; // аналогично
		$metadata['normalized']=false; // пока не обработано функцией normalizeData();
	}
	
	public function normalizeData(&$metadata=null)
	{
		$this->data['value']=preg_replace('/(\r\n|\r)/', '\n', $this->data['value']);
		$meta=parent::normalizeData($metadata);
	}
}

?>