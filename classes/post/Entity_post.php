<?
class Entity_post extends Entity_work
{
	static $mod_formats=array(
		'display'=>'%div%',
		'div'=>'<div><h3>%>title%</h3><p>%attribution% (%dates%)<p>%details%</p></div>',
		'attribution'=>'Автор: %>contirbutor%',
		'dates'=>array('self', 'dates'),
		'details'=>'%error[class err]%'
	);
	
	public function setFormats()
	{
		parent::setFormats();
		$this->formats=array_merge($this->formats, self::$mod_formats);
	}
}
?>