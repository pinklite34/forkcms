<?php

/**
 * Spoon Library
 *
 * This source file is part of the Spoon Library. More information,
 * documentation and tutorials can be found @ http://www.spoon-library.be
 *
 * @package			errors
 *
 *
 * @author			Davy Hellemans <davy@spoon-library.be>
 * @author			Tijs Verkoyen <tijs@spoon-library.be>
 * @since			0.1.1
 */


// redefine the exception handler
set_exception_handler('exceptionHandler');


/**
 * Prints out the thrown exception in a more readable manner
 *
 * @return	void
 * @param	SpoonException $exception
 */
function exceptionHandler($exception)
{
	// fetch trace stack
	$aTrace = $exception->getTrace();

	// class & function exist and are spoon related
	if(isset($aTrace[0]['class']) && isset($aTrace[0]['function']) && strtolower(substr($aTrace[0]['class'], 0, 5)) == 'spoon')
	{
		$documentationUrl = $aTrace[0]['class'] .'/'. strtolower($aTrace[0]['function']);

		// build documentation url
		$documentation = '&raquo; <a href="http://docs.spoon-library.be/search/'. $documentationUrl .'">view documentation</a>';
	}

	// specific name
	$name = (method_exists($exception, 'getName')) ? $exception->getName() : 'UnknownException';

	// should exceptions be shown?
	if(!defined('SPOON_DEBUG')) define('SPOON_DEBUG', true);

	// request uri?
	if(!isset($_SERVER['REQUEST_URI'])) $_SERVER['REQUEST_URI'] = '';

	// generate output
	$output = '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>'. $name .'</title>
			<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
			<style type="text/css">
			body
			{
				background-color: #f2f2f2;
				color: #000;
				font-family: verdana, tahoma, arial;
				font-size: 10px;
				margin: 10px;
				padding: 0;
			}

			#container
			{
				margin: 0 auto;
				/*position: absolute;*/
				width: 550px;
			}

			#container-main, #container-stack, #container-variables
			{
				background-color: #eee;
				border: 1px solid #b2b2b2;
				margin: 0 0 10px 0;
			}

			#main, #stack, #variables
			{
				margin: 10px 10px 10px 10px;
			}

			#main h1, #stack h1, #variables h1
			{
				font-size: 12px;
				margin: 0 0 10px 0;
				padding: 0;
			}

			#main dl, #stack dl, #variables dl
			{
				border-top: 1px solid #999;
				margin: 0;
				padding: 5px 0 0 0;
			}

			#main dt, #stack dt, #variables dt
			{
				float: left;
				font-weight: bold;
				margin: 0;
				padding: 0;
				text-align: right;
			}

			#main dd, #stack dd, #variables dd
			{
				margin: 0 0 5px 100px;
				padding: 0;
			}

			#main dd pre, #stack dd pre, #variables dd pre
			{
				font-family: verdana, tahoma, arial;
				font-size: 10px;
				margin: 0;
				padding: 0;
			}
			</style>
		</head>

		<body>
			<div id="container">

				<!-- main -->
				<div id="container-main">
					<div id="main">
						<h1>'. $name .' &raquo; Main</h1>
						<dl>
							<dt>Message</dt>
								<dd>'. $exception->getMessage() .'</dd>
							<dt>File</dt>
								<dd>'. wordwrap($exception->getFile(), 70, '<br />', true) .'</dd>
							<dt>Line</dt>
								<dd>'. $exception->getLine() .'</dd>
							<dt>Date</dt>
								<dd>'. date('Y/m/d @ H:i:s') .'</dd>
							<dt>URL</dt>
								<dd>';

								// request url
								$output .= (isset($_SERVER['REQUEST_URI'])) ? wordwrap($_SERVER['REQUEST_URI'], 70, '<br />', true) : 'Unknown Request URL';
								$output .= '</dd>
							<dt>Referring URL</dt>
								<dd>';

								// referring url
								$output .= (isset($_SERVER['HTTP_REFERER'])) ? '<a href="'. $_SERVER['HTTP_REFERER'] .'">'. $_SERVER['HTTP_REFERER'] .'</a>' : 'Unknown Referrer';
								$output .= '</dd>
							<dt>Request Method</dt>
								<dd>'. $_SERVER['REQUEST_METHOD'] .'</dd>';

								// no documentation ?
								if(isset($documentation))
								{
									$output .= '<dt>Documentation</dt>
													<dd>'. $documentation .'</dd>';
								}

							// continue output
							$output .= '
						</dl>
					</div>
				</div>

				<!-- variables -->
					<div id="container-variables">
						<div id="variables">
							<h1>'. $name .' &raquo; Variables</h1>';


								// $_GET has items
								if(isset($_GET))
								{
									// open defition list
									$output .= "<dl>\r\n";

									// title + array
									$output .= "<dt>\$_GET</dt>\r\n<dd><pre>". print_r($_GET, true) ."</pre></dd>\r\n";

									// close definition list
									$output .= "</dl>\r\n";
								}

								// $_POST has items
								if(isset($_POST))
								{
									// open defition list
									$output .= "<dl>\r\n";

									// title + array
									$output .= "<dt>\$_POST</dt>\r\n<dd><pre>". print_r($_POST, true) ."</pre></dd>\r\n";

									// close definition list
									$output .= "</dl>\r\n";
								}

								// $_SESSION has items
								if(isset($_SESSION))
								{
									// open defition list
									$output .= "<dl>\r\n";

									// title + array
									$output .= "<dt>\$_SESSION</dt>\r\n<dd><pre>". print_r($_SESSION, true) ."</pre></dd>\r\n";

									// close definition list
									$output .= "</dl>\r\n";
								}

								// $_COOKIE has items
								if(isset($_COOKIE))
								{
									// open defition list
									$output .= "<dl>\r\n";

									// title + array
									$output .= "<dt>\$_COOKIE</dt>\r\n<dd><pre>". print_r($_COOKIE, true) ."</pre></dd>\r\n";

									// close definition list
									$output .= "</dl>\r\n";
								}

							$output .= '
						</div>
					</div>

				<!-- stack -->
				<div id="container-stack">
					<div id="stack">
						<h1>'. $name .' &raquo; Trace</h1>';

							// trace has items
							if(count($exception->getTrace()) != 0)
							{
								// fetch entire stack
								$entireTraceStack = $exception->getTrace();

								// loop elements
								foreach ($entireTraceStack as $traceStack)
								{
									// open defintion list
									$output .= "<dl>\r\n";

									// file & line
									$output .= "<dt>File</dt>\r\n";
									$output .= '<dd>'. ((isset($traceStack['file'])) ? wordwrap($traceStack['file'], 70, '<br />', true) : 'Unknown') ."</dd>\r\n";
									$output .= "<dt>Line</dt>\r\n";
									$output .= '<dd>'. ((isset($traceStack['line'])) ? $traceStack['line'] : 'Unknown') ."</dd>\r\n";

									// class & function
									if(isset($traceStack['class'])) $output .= "<dt>Class</dt>\r\n<dd>". $traceStack['class'] ."</dd>\r\n";
									if(isset($traceStack['function'])) $output .= "<dt>Function</dt>\r\n<dd>". $traceStack['function'] ."</dd>\r\n";

									// function arguments
									if(isset($traceStack['args']) && count($traceStack['args']) != 0)
									{
										// argument title
										$output .= "<dt>Argument(s)</dt>\r\n<dd><pre>". print_r($traceStack['args'], true) ."</pre></dd>\r\n";
									}

									// close defintion list
									$output .= "</dl>\r\n";
								}
							}

							// no trace
							else $output .= 'No trace available.';

							// continue output generation
							$output .= '
						</div>
					</div>
				</div>
			</body>
		</html>
		';

	// obfuscate
	if(method_exists($exception, 'getObfuscate') && count($exception->getObfuscate()) != 0)
	{
		$output = str_replace($exception->getObfuscate(), '***', $output);
	}

	// debugging enabled (show output)
	if(SPOON_DEBUG) echo $output;

	// debugging disabled
	else
	{
		// show custom message
		if(defined('SPOON_DEBUG_MESSAGE')) echo SPOON_DEBUG_MESSAGE;
	}

	// mail it?
	if(defined('SPOON_DEBUG_EMAIL') && SPOON_DEBUG_EMAIL != '')
	{
		// e-mail headers
		$headers = "MIME-Version: 1.0\n";
		$headers .= "Content-type: text/html; charset=iso-8859-15\n";
		$headers .= "X-Priority: 3\n";
		$headers .= "X-MSMail-Priority: Normal\n";
		$headers .= "X-Mailer: SpoonLibrary Webmail\n";
		$headers .= "From: Spoon Library <no-reply@spoon-library.com>\n";

		// send email
		@mail(SPOON_DEBUG_EMAIL, 'Exception Occured', $output, $headers);
	}

	// stop script execution
	exit;
}

?>