<?php namespace esoTalk\Laravel;

class View extends \Laravel\View {
	
	protected function path($view)
	{
		if ($view[0] == '/')
		{
			return $view;
		}

		return parent::path($view);
	}

}