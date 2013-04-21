<?
class Entity_work extends Entity_combo
{
	public $model = array(
		'title'=>array(
			'type'=>'title'
		),
		'contributor'=> array(
			'type'=>'user'
		),
		'approved'=>array(
			'type'=>'approved_state',
			'default'=>'pending'
		)
		'date_contributed'=>array(
			'type'=>'date'
		),
		'date_published'=>array(
			'type'=>'date'
		),
		'date_bumped'=>array(
			'type'=>'date'
		)
	);
}
?>