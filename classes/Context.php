<?
class Context
{
	public $purpose='';
	// dryrun - "сухой прогон", для дебага. Не используется.
	// get_from_db - не используется.
	// display - показ пользователю данных.
	// dbmod - модифицирование данных в БД (скрытая страница).
	// new - форма создания новой сущности.
	// dbnew - запись новой сущности в БД (скрытая странца).
	// edit - форма редактирования существующей сущности.
	
	static $purpose_steps=array(
		'display'=>array('get_from_db', 'echo_values'),
		'new'=>array('get_default', 'echo_input'),
		'dbmod'=>array('get_from_db', 'get_input', 'save_to_db', 'echo_input'),
		'dbnew'=>array('get_input', 'save_to_db', 'echo_input'),
		'edit'=>array('get_from_db', 'echo_input')
	);
	public $steps=array(), $step=null, $endstep_key='';
	
	public $media='html'; // xml, json - пока не используются.
	public $user=null;
	public $root=null;
	
	public function __construct($purpose, $user=null)
	{
		$this->purpose=$purpose;
		$this->steps=static::$purpose_steps[$this->purpose];
		end($this->steps); $this->endstep_key=key($this->steps);
		$this->step=reset($this->steps);
		$this->user=$user;
	}
	
	public function execute($step=null)
	{
		$result='';
		if (is_null($step))
		{
			$this->step=null;
			while ($this->nextStep())
			{
				$res=$this->executeCurrentStep();
				if (is_string($res)) $result.=$res; 
			}
		}
		return $result;
	}
	
	public function executeCurrentStep()
	{
		if ($this->step=='get_from_db')
		{
			$this->root->parse('', $this);
			EntityRetriever::retrieve();
			$result=true;
		}
		elseif ( ($this->step=='echo_input') || ($this->step=='echo_values') )
		{
			$result=$this->root->parse('', $this);
		}
		return $result;
	}
	
	public function nextStep()
	{
		if (is_null($this->step))
		{
			$this->step=reset($this->steps);
			return true;
		}
		elseif (key($this->steps)==$this->endstep_key) return false;
		else
		{
			$this->step=next($this->steps);
			return true;
		}
	}
	
	// сообщает, является ли данный этап таким этапом, где надо не выводить данные, а запрашивать их.
	public function do_req()
	{
		return ($this->step=='get_from_db');
	}
	
	// сообщает, является ли текущий процесс созданием новой сущности, у которой нет готовой записи в БД.
	public function create_blanks()
	{
		static $create_blanks=array('new', 'dbnew');
		
		return in_array($this->purpose, $create_blanks, 1);	
	}
	
	// сообщает, является ли данный этап таким этапом, где данные выводятся пользователю для просмотра.
	public function display_values()
	{		
		return ($this->step=='echo_values');
	}
	
	// сообщает, является ли даннй этап таким этапом, где данные выводятся пользователю для редактирования
	public function display_input()
	{		
		return ($this->step=='echo_input');
	}
	
	public function display_something()
	{
		if ($this->display_values()) return true;
		if ($this->display_input()) return true;
		return false;
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