<?php

include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
 * Example GUI class for question type plugins
 *
 * @author	Frank Bauer <frank.bauer@fau.de>
 * @version	$Id:  $
 * @ingroup ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assCodeQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 */
class assCodeQuestionGUI extends assQuestionGUI
{
	/**
	 * @const	string	URL base path for including special javascript and css files
	 */
	const URL_PATH = "./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assCodeQuestion";

	/**
	 * @const	string 	URL suffix to prevent caching of css files (increase with every change)
	 * 					Note: this does not yet work with $tpl->addJavascript()
	 */
	const URL_SUFFIX = "?css_version=1.5.0";

	/**
	 * @var ilassCodeQuestionPlugin	The plugin object
	 */
	var $plugin = null;


	/**
	 * @var assCodeQuestion	The question object
	 */
	var $object = null;
	
	/**
	* Constructor
	*
	* @param integer $id The database id of a question object
	* @access public
	*/
	public function __construct($id = -1)
	{
		parent::__construct();
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assCodeQuestion");
		$this->plugin->includeClass("class.assCodeQuestion.php");
		$this->object = new assCodeQuestion();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}		
	}

	function getLanguage() {
		return $this->object->getLanguage();
	}

	var $didPrepare = false;
	private function getLanguageData(){
		$language = $this->getLanguage();
		$hljslanguage = $language;
		$mode = $language;

		if ($language=="java") {
			$language = "clike";
			$mode = "text/x-java";
		} else if ($language=="c++") {
			$language = "clike";
			$mode = "text/x-c++src";
		} else if ($language=="c") {
			$language = "clike";
			$mode = "text/x-csrc";
		} else if ($language=="objectivec") {
			$language = "clike";
			$mode = "text/x-objectivec";
		} 

		return array(
			'cmLanguage'=>$language,
			'cmMode'=>$mode,
			'hljsLanguage'=>$hljslanguage
			);
	}
	private function prepareTemplate($force=false) {
		if ($this->didPrepare && !$force) return;
		$this->didPrepare = true;
		
		$lngData = $this->getLanguageData();
		//$this->tpl->addJavascript(self::URL_PATH.'/js/javapoly/javapoly.js');
		if ($lngData['cmLanguage'] == "python") {
			$this->tpl->addJavascript(self::URL_PATH.'/js/skulpt/skulpt.min.js');
			$this->tpl->addJavascript(self::URL_PATH.'/js/skulpt/skulpt-stdlib.js');
		}

		$this->tpl->addCss(self::URL_PATH.'/js/codemirror/lib/codemirror.css');
		$this->tpl->addCss(self::URL_PATH.'/js/codemirror/theme/solarized.css');
		$this->tpl->addCss(self::URL_PATH.'/js/highlight.js/styles/solarized-dark.css');
		$this->tpl->addJavascript(self::URL_PATH.'/js/codemirror/lib/codemirror.js');
		$this->tpl->addJavascript(self::URL_PATH.'/js/highlight.js/highlight.pack.js');

		$this->tpl->addJavascript(self::URL_PATH.'/js/codemirror/mode/'.$lngData['cmLanguage'].'/'.$lngData['cmLanguage'].'.js');
		$this->tpl->addJavascript(self::URL_PATH.'/js/helper.js');
		$this->tpl->addOnLoadCode('initSolutionBox("'.$lngData['cmMode'].'");');
		$this->tpl->addOnLoadCode("hljs.configure({useBR: false});$('pre[class=".$lngData['hljsLanguage']."][usebr=no]').each(function(i, block) { hljs.highlightBlock(block);});");
		$this->tpl->addOnLoadCode("hljs.configure({useBR: true});$('pre[class=".$lngData['hljsLanguage']."][usebr=yes]').each(function(i, block) { hljs.highlightBlock(block);});");
	}

	/**
	 * Creates an output of the edit form for the question
	 *
	 * @param bool $checkonly
	 * @return bool
	 */
	public function editQuestion($checkonly = FALSE)
	{
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";

		global $lng;
		$lngData = $this->getLanguageData();

		$save = $this->isSaveCommand();
		$this->getQuestionTemplate();
		$this->prepareTemplate();

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(TRUE);
		$form->setTableWidth("100%");
		$form->setId("codeqst");

		$this->addBasicQuestionFormProperties( $form );

		// Here you can add question type specific form properties
		// We only add an input field for the maximum points
		// NOTE: in complex question types the maximum points are summed up by partial points
		$points = new ilNumberInputGUI($lng->txt('maximum_points'),'points');
		$points->setSize(3);
		$points->setMinValue(1);
		$points->allowDecimals(0);
		$points->setRequired(true);
		$points->setValue($this->object->getPoints());
		$form->addItem($points);

		// Add Source Code Type Selection
		$select = new ilSelectInputGUI($this->plugin->txt('source_lang'), 'source_lang');
        $select->setOptions(array(
			'c'=>'C', 
			'c++'=>'C++',
			'css'=>'CSS', 
			'fortran'=>'Fortran', 
			'html'=>'HTML', 
			'java'=>'Java',
			'javascript'=>'JavaScript',
			'objectivec'=>'Objective-C',
			'perl'=>'Perl',
			'php'=>'PHP',
			'python'=>'Python',
			'ruby'=>'Ruby',
			'sql'=>'SQL',
			'xml'=>'XML')
			);
		$select->setValue($this->getLanguage());
		$form->addItem($select);

		$allowRun = new ilCheckboxInputGUI($this->plugin->txt('allow_run'), 'allow_run');
		$allowRun->setChecked($this->object->getAllowRun());
		$allowRun->setValue('true');
		$form->addItem($allowRun);

		$txt1 = new ilTextAreaInputGUI($this->plugin->txt('code_prefix'), 'code_prefix');	
		$txt1->usePurifier(false);				
		$txt1->setUseRte(TRUE);		
        $txt1->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
		$txt1->setValue($this->object->getPrefixCode());
		$form->addItem($txt1);
		$this->tpl->addOnLoadCode('$("[name=code_prefix]").each(function(i, block) { CodeMirror.fromTextArea(block, {lineNumbers: true, mode:"'.$lngData['cmMode'].'", theme:"solarized dark"});});');

		$txt2 = new ilTextAreaInputGUI($this->plugin->txt('code_postfix'), 'code_postfix');	
		$txt2->usePurifier(false);			
		$txt2->setUseRte(TRUE);		
        $txt2->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags("assessment"));
		$txt2->setValue($this->object->prepareTextareaOutput($this->object->getPostfixCode()));
		$form->addItem($txt2);
		$this->tpl->addOnLoadCode('$("[name=code_postfix]").each(function(i, block) { CodeMirror.fromTextArea(block, {lineNumbers: true, mode:"'.$lngData['cmMode'].'", theme:"solarized dark"});});');

		


		$this->populateTaxonomyFormSection($form);
		$this->addQuestionFormCommandButtons($form);

		$errors = false;

		if ($save)
		{
			$form->setValuesByPost();
			$errors = !$form->checkInput();
			$form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
			if ($errors) $checkonly = false;
		}

		if (!$checkonly)
		{
			$this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
		}
		return $errors;
	}

	/**
	 * Evaluates a posted edit form and writes the form data in the question object
	 *
	 * @param bool $always
	 * @return integer A positive value, if one of the required fields wasn't set, else 0
	 */
	public function writePostData($always = false)
	{
		$hasErrors = (!$always) ? $this->editQuestion(true) : false;
		if (!$hasErrors)
		{			
			$this->writeQuestionGenericPostData();

			// Here you can write the question type specific values
			$this->object->setPoints((int) $_POST["points"]);
			$this->object->setLanguage((string) $_POST["source_lang"]);
			$this->object->setPrefixCode((string) $_POST["code_prefix"]);
			$this->object->setPostfixCode((string) $_POST["code_postfix"]);
			$this->object->setAllowRun(((string) $_POST["allow_run"])=='true');

			$this->saveTaxonomyAssignments();
			return 0;
		}
		return 1;
	}

	
	/**
	 * Show the question in Test mode
	 * (called from ilTestOutputGUI)
	 * 
	 * @param string $formaction			The action link for the form
	 * @param integer $active_id			The active user id
	 * @param integer $pass					The test pass
	 * @param boolean $is_postponed			Question is postponed
	 * @param boolean $use_post_solutions	Use post solutions
	 * @param boolean $show_feedback		Show a feedback
	 */
	/*public function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions, $show_feedback); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
	}*/

	/**
	 * Get the html output of the question for different usages (preview, test)
	 *
	 * @param    array            values of the user's solution
	 *
	 * @see assAccountingQuestion::getSolutionSubmit()
	 */
	private function getQuestionOutput($value1, $template=nil, $show_question_text=true)
	{
		$this->prepareTemplate();
		$language = $this->getLanguage();		
		$runCode = "";
		if ($language == "python" && $this->object->getAllowRun()) {
			$runCode = '<input type="button" value="run" onclick="runPythonInTest(\'question'.$this->object->getId().'value1\')"><pre id="output">...</pre>';
		} 
		
		// fill the question output template
		// in out example we have 1:1 relation for the database field

		if ($template == nil) {
			$template = $this->plugin->getTemplate("tpl.il_as_qpl_codeqst_output.html");
		}

		if ($show_question_text==true)
		{
			$questiontext = $this->object->getQuestion();
			$questiontext = $this->object->prepareTextareaOutput($questiontext, TRUE);
			$questiontext = str_replace('[code]', '<pre class="'.$language.'" usebr="no">', $questiontext);
			$questiontext = str_replace('[/code]', '</pre>', $questiontext);
			$template->setVariable("QUESTIONTEXT", $questiontext);
		} else {
			$template->setVariable("QUESTIONTEXT", "");
		}

		$template->setVariable("LANGUAGE", $language);
		$template->setVariable("RUN_CODE_HTML", $runCode);

		$template->setVariable("QUESTION_ID", $this->object->getId());
		$template->setVariable("LABEL_VALUE1", $this->plugin->txt('label_value1'));

		$template->setVariable("OPENING_CODE", $this->object->getPrefixCode());
		$template->setVariable("VALUE1", empty($value1) ? "" : ilUtil::prepareFormOutput($value1));
		$template->setVariable("CLOSING_CODE", $this->object->getPostfixCode());

		$questionoutput = $template->get();
		return $questionoutput;
	}

	/**
	 * Get the HTML output of the question for a test
	 * (this function could be private)
	 * 
	 * @param integer $active_id			The active user id
	 * @param integer $pass					The test pass
	 * @param boolean $is_postponed			Question is postponed
	 * @param boolean $use_post_solutions	Use post solutions
	 * @param boolean $show_feedback		Show a feedback
	 * @return string
	 */
	public function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{
		include_once "./Modules/Test/classes/class.ilObjTest.php";
		if (is_null($pass))
		{
			$pass = ilObjTest::_getPass($active_id);
		}
		$solutions = $this->object->getSolutionValues($active_id, $pass);

		// there may be more tham one solution record
		// the last saved wins
		if (is_array($solutions))
		{
			foreach ($solutions as $solution)
			{
				$value1 = isset($solution["value1"]) ? $solution["value1"] : "";
			}
		}

		$questionoutput = $this->getQuestionOutput($value1);
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput;
	}

	
	/**
	 * Get the output for question preview
	 * (called from ilObjQuestionPoolGUI)
	 * 
	 * @param boolean	show only the question instead of embedding page (true/false)
	 */
	public function getPreview($show_question_only = FALSE, $showInlineFeedback = FALSE)
	{
		if( is_object($this->getPreviewSession()) )
		{
			$solution = $this->getPreviewSession()->getParticipantsSolution();
		}

		$questionoutput = $this->getQuestionOutput($solution['value1']);		
		if(!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}

	/**
	 * Get the question solution output
	 * @param integer $active_id             The active user id
	 * @param integer $pass                  The test pass
	 * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
	 * @param boolean $result_output         Show the reached points for parts of the question
	 * @param boolean $show_question_only    Show the question without the ILIAS content around
	 * @param boolean $show_feedback         Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
	 * @return The solution output of the question as HTML code
	 */
	function getSolutionOutput(
		$active_id,
		$pass = NULL,
		$graphicalOutput = FALSE,
		$result_output = FALSE,
		$show_question_only = TRUE,
		$show_feedback = FALSE,
		$show_correct_solution = FALSE,
		$show_manual_scoring = FALSE,
		$show_question_text = TRUE
	)
	{
		// get the solution template
		$template = $this->plugin->getTemplate("tpl.il_as_qpl_codeqst_output_solution.html");	

		// get the solution of the user for the active pass or from the last pass if allowed
		$solutions = array();
		if (($active_id > 0) && (!$show_correct_solution))
		{
			// get the answers of the user for the active pass or from the last pass if allowed
			$solutions = $this->object->getSolutionValues($active_id, $pass);

			$template->setVariable("NAME_MODIFIER", "");
		}
		else
		{
			// show the correct solution
			$solutions = array(array(
				"value1" => $this->plugin->txt("any_text")				
			));
			$template->setVariable("NAME_MODIFIER", "_SOL");
		}

		// loop through the saved values if more records exist
		// the last record wins
		// adapt this to your structure of answers
		foreach ($solutions as $solution)
		{
			$value1 = isset($solution["value1"]) ? $solution["value1"] : "";			
		}

		
			
		$this->tpl->addOnLoadCode('runPythonInSolution();');

		if (($active_id > 0) && (!$show_correct_solution))
		{
			if ($graphicalOutput)
			{
				// copied from assNumericGUI, yet not really understood
				if($this->object->getStep() === NULL)
				{
					$reached_points = $this->object->getReachedPoints($active_id, $pass);
				}
				else
				{
					$reached_points = $this->object->calculateReachedPoints($active_id, $pass);
				}

				// output of ok/not ok icons for user entered solutions
				// in this example we have ony one relevant input field (points)
				// so we just need to tet the icon beneath this field
				// question types with partial answers may have a more complex output
				/*if ($this->object->getReachedPoints($active_id, $pass) == $this->object->getMaximumPoints())
				{
					$template->setCurrentBlock("icon_ok");
					$template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.svg"));
					$template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock("icon_ok");
					$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.svg"));
					$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
					$template->parseCurrentBlock();
				}*/
			}
		}

		$questionoutput = $this->getQuestionOutput($value1, $template, $show_question_text);
		
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		$feedback = ($show_feedback) ? $this->getGenericFeedbackOutput($active_id, $pass) : "";
		if (strlen($feedback)) $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $feedback, true ));

		$solutionoutput = $solutiontemplate->get();
		if(!$show_question_only)
		{
			// get page object output
			$solutionoutput = $this->getILIASPage($solutionoutput);
		}
		return $solutionoutput;
	}

	/**
	 * Returns the answer specific feedback for the question
	 * 
	 * @param integer $active_id Active ID of the user
	 * @param integer $pass Active pass
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	function getSpecificFeedbackOutput($active_id, $pass)
	{
		// By default no answer specific feedback is defined
		return $this->object->prepareTextareaOutput($output, TRUE);
	}
	
	
	/**
	* Sets the ILIAS tabs for this question type
	* called from ilObjTestGUI and ilObjQuestionPoolGUI
	*/
	public function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;
		
		$this->ctrl->setParameterByClass("ilpageobjectgui", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if ($_GET["q_id"])
		{
			if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
			{
				// edit page
				$ilTabs->addTarget("edit_page",
					$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"", "", $force_active);
			}
	
			// edit page
			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "preview"),
				array("preview"),
				"ilAssQuestionPageGUI", "", $force_active);
		}

		$force_active = false;
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";

			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			$commands = $_POST["cmd"];

			// edit question properties
			$ilTabs->addTarget("edit_properties",
				$url,
				array("editQuestion", "save", "cancel", "cancelExplorer", "linkChilds", 
				"parseQuestion", "saveEdit"),
				$classname, "", $force_active);
		}

		// add tab for question feedback within common class assQuestionGUI
		$this->addTab_QuestionFeedback($ilTabs);

		// add tab for question hint within common class assQuestionGUI
		$this->addTab_QuestionHints($ilTabs);

		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("solution_hint",
				$this->ctrl->getLinkTargetByClass($classname, "suggestedsolution"),
				array("suggestedsolution", "saveSuggestedSolution", "outSolutionExplorer", "cancel",
					"addSuggestedSolution","cancelExplorer", "linkChilds", "removeSuggestedSolution"
				),
				$classname,
				""
			);
		}

		// Assessment of questions sub menu entry
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}
		
		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];
			$ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
	}
}
?>
