<?
class Context
{
	public $purpose='';
	// dryrun - "сухой прогон", для дебага. Не используется.
	// preprocess - не используется.
	// display - показ пользователю данных.
	// dbmod - модифицирование данных в БД (скрытая страница).
	// new - форма создания новой сущности.
	// dbnew - запись новой сущности в БД (скрытая странца).
	// edit - форма редактирования существующей сущности.
	
	public $media='html'; // xml, json - пока не используются.
	public $user=null;
	public $root=null;
	
	public function __construct($purpose, $user=null)
	{
		$this->purpose=$purpose;
		$this->user=$user;
	}
	
	// сообщает, является ли данный этап таким этапом, где надо не выводить данные, а запрашивать их.
	public function do_req()
	{
		static $do_req=array('preprocess', 'dbmod');
		
		return in_array($this->purpose, $do_req);
	}
	
	// сообщает, является ли текущий процесс созданием новой сущности, у которой нет готовой записи в БД.
	public function create_blanks()
	{
		static $create_blanks=array('new', 'dbnew');
		
		return in_array($this->purpose, $create_blanks);	
	}
	
	// сообщает, является ли данный этап таким этапом, где данные выводятся пользователю для просмотра.
	public function display_values()
	{
		static $display_values=array('display');
		
		return in_array($this->purpose, $display_values);
	}
	
	// сообщает, является ли даннй этап таким этапом, где данные выводятся пользователю для редактирования
	public function display_input()
	{
		static $display_values=array('edit', 'new');
		
		return in_array($this->purpose, $display_values);	
	}
	
	// проверяет, является ли данный формат форматом текущего процесса.
	public function media($m)
	{
		return $m==$this->media;
	}
	
	// проверяет, является ли данная сущность корневой сущностью данного процесса
	public function is_root($entity)
	{
		return $entity===$this->root;
	}
}
?>