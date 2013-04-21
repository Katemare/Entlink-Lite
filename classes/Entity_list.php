<?
class Entity_list extends Entity_combo
{

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