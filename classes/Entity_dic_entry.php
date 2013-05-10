<?

class Entity_dic_entry extends Entity_combo
{
	public $model=array(
		'code'=>array(
			'type'=>'string',
			'table'=>'entlink_dic', // STUB
			'field'=>'code'
		),
		'lang'=>array(
			'type'=>'string',
			'table'=>'entlnk_dic',
			'field'=>'lang'
		),
		'str'=>array(
			'type'=>'string',
			'table'=>'entlink_dic',
			'field'=>'lang'
		)
	);
}

?>