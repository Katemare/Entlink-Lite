<?
class Entity_dic extends Entity_list
{
	public $model=array(
		'member'=>array(
			'type'=>'dic_entry'
		)
	);
	
	public $mod_formats=array(
		'dic'=>array('func', 'translate')
	);
	
	public function translate($args='', $context=null)
	{
		$this->analyzeContext($context);
		
		// ERR - нет обработки ошибки, если не передан код.
		// ERR - если вызов осуществлялся не с помощью директивы dic, а через форматирование, то пока никак не задаётся аргумент call.
		if (isset($args['lang'])) $lang=$args['lang']; else $lang=$context->lang;
		$member=$this->fillMember_dic($code, $lang);
		if ($context->do_req())
		{
			
		}
		return $result;
	}
	
	public function fillMember_dic($code, $lang)
	{
		$newcode=$code.'.'.$lang;
		$submodel=$this->model['member'];
		$submodel['lang']=$lang;
		$result=parent::fillMember($newcode, $submodel);
		$result->setData('code', $code);
		return $result;
	}
}
?>