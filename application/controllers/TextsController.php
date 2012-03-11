<?php
/** Zend_Controller_Action */
class TextsController extends Zend_Controller_Action
{
	private $text;
	
	public function init() {
		global $st_lang;
		$this->text = $st_lang;
	}
	
	public function indexAction() {
		$this->render('index');
	}
	
	public function aboutAction() {
		$this->view->assign('title', $this->text['text_about_title']);
		$this->view->assign('text', $this->text['text_about_text']);
		$this->render('index');
	}
	
	public function benefitsAction() {
		$this->view->assign('title', $this->text['text_features_title']);
		$this->view->assign('text', $this->text['text_features_text']);
		$this->render('index');
	}
	
	public function helpAction() {
		$s_helpExpenses = '
		En la vista de expenses podemos distinguir dos partes. La parte de
		la izquierda es donde podemos introducir los gastos que vamos teniendo.
		Para cada gasto debemos introducir el gasto en sí mismo, especificar
		la categoría o subcategoría a la que lo asignamos, una nota opcional
		y la fecha (por defecto la fecha de hoy). Debajo tenemos todos los gastos
		que hemos introducido en el mes actual. Para borrar un gasto basta con
		clicar en la X, y para editar un gasto la E.
		En la parte derecha vemos 3 partes. La primera es un gráfico de tarta
		de los gastos que hemos apuntado este mes. Debajo la suma de gastos
		por categoría, y en caso de que hayamos creado un presupuesto, la
		comparación con lo presupuestado en verde si cumplimos nuestros objetivos
		y en rojo si hemos superado el límite marcado. Finalmente, el tercer
		gráfico muestra el gasto mensual de todos los meses.
		';
	}
}
?>