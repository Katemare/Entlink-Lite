<?
class Entity_list extends Entity_combo
{
	public $model=array(
		'member'=>array() // inherit me!
	)

	public function fillMember($code, $submodel=null)
	{
		if ( (is_null($submodel)) && (!isset($this->model[$code])) ) $submodel=$this->model['member'];
		return parent::flllMember($code, $submodel);
	}

	public function analyzeData_iterate(&$metadata)
	{
		foreach ($this->data as $index->$member)
		{
			if ($member instanceof Entity) $this->analyzeData_member($metadata, $member);
			// else // ERR
		}
	}
}
?>