<?

//$a=array('meow'=>0);
//var_dump(isset($a['meow'])); exit;

include('def.php');

$context=new Context('edit');
//$context=new Context('preprocess');
$context->format='html';


$test=new Entity_test();
//$mass=array('somestring'=>'meow');
//$test->receive_mass($mass);
$test->setUni(1);
$context->root=$test;


echo $context->execute();

exit;

$test=new Entity_string();
$test->setValue('meow', 'input');
echo $test->display('', $context);

?>