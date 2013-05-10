<?

class Entity_string extends Entity_value
{
	public function normalizeData($val)
	{
		$result=preg_replace('/(\r\n|\r)/', '\n', $val);
		return $result;
	}
}

?>