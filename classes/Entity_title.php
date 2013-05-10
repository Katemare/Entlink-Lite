<?
class Entity_title extends Entity_string
{
	public $maxlength=200, $invalid_ex='\x0-\x1F';

	public function isValid($val)
	{
		return preg_match('/['.$this->invalid_ex.']/', $val);
	}
	
	public function correctData($val)
	{
		
	}
}
?>