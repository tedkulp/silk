<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008-2011 Ted Kulp
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

require_once(dirname(dirname(dirname(__FILE__))) . '/silk.api.php');

use \silk\test\WebTestCase;
use \silk\form\Form;

class FormTest extends WebTestCase
{
	public function beforeTest()
	{
		\silk\action\Route::clearRoutes();
		\silk\action\Route::buildDefaultRoutes();
	}

	public function testBasicForm()
	{
		$form = new Form('test', array('id' => 'the_id', 'class' => 'the_class', 'remote' => true));
		$this->assertNotNull($form);
		$this->assertEquals($form->getId(), 'the_id');
		$this->assertEquals($form->getClass(), 'the_class');
		$this->assertEquals($form->getName(), 'test');
		$this->assertNull($form->getBlah());

		$form = new Form('test');
		$this->assertNotNull($form);
		$form = $form->setId('the_id')->setClass('the_class')->setRemote(true);
		$this->assertEquals($form->getId(), 'the_id');
		$this->assertEquals($form->getClass(), 'the_class');
		$this->assertTrue($form->getRemote());
		$this->assertEquals($form->getName(), 'test');
		$this->assertNull($form->getBlah());

		$result = $form->render();
		$this->assertContains('id="the_id"', $result);
		$this->assertContains('class="the_class"', $result);
		$this->assertContains('data-remote="true"', $result);
		$this->assertContains('method="POST"', $result);
		$this->assertContains('<form', $result);
		$this->assertContains('</form>', $result);
	}

	public function testArrayAccess()
	{
		$form = new Form('test');

		$this->assertFalse(isset($form['thing']));
		$this->assertFalse(isset($form['the_password']));

		$form->addField('TextBox', 'thing');
		$form['the_password'] = new \silk\form\elements\Password($form, 'the_password');

		$this->assertTrue(isset($form['thing']));
		$this->assertTrue(isset($form['the_password']));

		$result = $form->render();
		$this->assertContains('type="text"', $result);
		$this->assertContains('type="password"', $result);

		unset($form['thing']);
		unset($form['the_password']);

		$this->assertFalse(isset($form['thing']));
		$this->assertFalse(isset($form['the_password']));

		$result = $form->render();
		$this->assertNotContains('type="text"', $result);
		$this->assertNotContains('type="password"', $result);
	}

	public function testTextBox()
	{
		$form = new Form('test');
		$form->addField('TextBox', 'test_textbox')->setClass('the_class')->setId('the_id');
		$result = $form->render();

		$this->assertContains('<input', $result);
		$this->assertContains('id="the_id"', $result);
		$this->assertContains('class="the_class"', $result);
		$this->assertContains('type="text"', $result);

		$form['test_textbox']->setValue('blah');
		$result = $form->render();
		$this->assertContains('value="blah"', $result);
	}

	public function testPassword()
	{
		$form = new Form('test');
		$form->addField('Password', 'test_password', array('class' => 'the_class', 'id' => 'the_id'));
		$result = $form->render();

		$this->assertContains('<input', $result);
		$this->assertContains('id="the_id"', $result);
		$this->assertContains('class="the_class"', $result);
		$this->assertContains('type="password"', $result);
	}

	public function testHidden()
	{
		$form = new Form('test');
		$form->addField('Hidden', 'test_hidden')->setValue('blah');
		$result = $form->render();

		$this->assertContains('<input', $result);
		$this->assertContains('type="hidden"', $result);
		$this->assertContains('name="test_hidden"', $result);
		$this->assertContains('value="blah"', $result);
	}

	public function testFieldset()
	{
		$form = new Form('test');
		$fs = $form->addFieldSet('config_options', array('legend' => 'Config Options'));
		$fs->addField('TextBox', 'test_textbox')->setClass('the_class')->setId('the_id');
		$form->addField('Password', 'test_password', array('class' => 'the_class', 'id' => 'the_id'));
		$result = $form->render();

		$this->assertContains('<fieldset><legend>Config Options</legend><input', $result);
		$this->assertContains('/fieldset><input', $result);
	}

	public function testSetValues()
	{
		$form = new Form('test');
		$fs = $form->addFieldSet('config_options', array('legend' => 'Config Options'));
		$fs->addField('TextBox', 'test_textbox');
		$form->addField('Password', 'test_password');
		$form->setValues(array('test_textbox' => 'Blah', 'test_password' => 'Blah 2'));
		$result = $form->render();

		$this->assertContains('value="Blah"', $result);
		$this->assertContains('value="Blah 2"', $result);
	}

	public function testGetField()
	{
		$form = new Form('test');
		$fs = $form->addFieldSet('config_options', array('legend' => 'Config Options'));
		$fs->addField('TextBox', 'test_textbox');
		$form->addField('Password', 'test_password');
		$form->setValues(array('test_textbox' => 'Blah', 'test_password' => 'Blah 2'));

		$this->assertContains('value="Blah"', $form->renderField('test_textbox'));
		$this->assertContains('value="Blah 2"', $form->renderField('test_password'));
		$this->assertContains('value="Blah"', $fs->renderField('test_textbox'));
		$this->assertEquals('', $fs->renderField('test_password'));
	}

	public function testDropdown()
	{
		$form = new Form('test');
		$form->addField('Dropdown', 'test_dropdown', array('options' => array('none' => 'None', 'test_1' => 'Test 1')));
		$this->assertContains('<select name="test_dropdown"><option>None</option><option>Test 1</option></select>', $form->renderField('test_dropdown'));

		$form->addField('Dropdown', 'test_dropdown', array('options' => array('none' => 'None', 'test_1' => 'Test 1'), 'value' => 'test_1'));
		$this->assertContains('<select name="test_dropdown"><option>None</option><option selected="selected">Test 1</option></select>', $form->renderField('test_dropdown'));

		$form->addField('Dropdown', 'test_dropdown', array('options' => array('none' => 'None', 'test_1' => 'Test 1')))->setValue('test_1');
		$this->assertContains('<select name="test_dropdown"><option>None</option><option selected="selected">Test 1</option></select>', $form->renderField('test_dropdown'));
	}

	public function testArrayName()
	{
		$form = new Form('test');
		$form->addField('TextBox', array('parent'));
		$form->addField('TextBox', array('parent', 'child'));
		$form->addField('TextBox', array('parent', 'child', 'grandchild'));
		
		$result = $form->render();
		$this->assertContains('name="parent"', $result);
		$this->assertContains('name="parent[child]"', $result);
		$this->assertContains('name="parent[child][grandchild]"', $result);

		$this->assertContains('name="parent[child]"', $form->renderField(array('parent', 'child')));
		$this->assertContains('name="parent[child]"', $form->renderField('parent[child]'));
	}

	public function testAlternateMethod()
	{
		$form = new Form('test', array('method' => 'put'));
		$result = $form->render();
		$this->assertContains('method="POST"', $result);
		$this->assertContains('name="_method" value="PUT"', $result);
	}

	public function testButtons()
	{
		$form = new Form('test');
		$form->addButton('Submit', array('value' => 'Submit!'));
		$form->addImageButton('Cancel', array('value' => 'Cancel!', 'src' => 'blah.gif'));
		$result = $form->render();
		$this->assertContains('name="Submit"', $result);
		$this->assertContains('value="Submit!"', $result);
		$this->assertContains('type="submit"', $result);
		$this->assertContains('name="Cancel"', $result);
		$this->assertContains('value="Cancel!"', $result);
		$this->assertContains('src="blah.gif"', $result);
		$this->assertContains('type="image"', $result);
	}

	public function testSubmitFunctions()
	{
		$response = $this->sendRequest('GET', '/form_test/test_post');
		$this->assertEquals('200 OK', $response->headers['Status']);
		$this->assertContains('Not Clicked', $response->content);
		$this->assertContains('Method: GET', $response->content);

		// Test that silk_form_var does something
		$response = $this->sendRequest('POST', '/form_test/test_post', array('thing' => 'test', 'Submit' => 'Submit', SILK_FORM_VAR => 'not_blah'));
		$this->assertEquals('200 OK', $response->headers['Status']);
		$this->assertContains('Not Clicked', $response->content);
		$this->assertContains('Method: POST', $response->content);

		// Now test some button that doesn't exist
		$response = $this->sendRequest('POST', '/form_test/test_post', array('thing' => 'test', 'Not Submit' => 'Submit', SILK_FORM_VAR => 'blah'));
		$this->assertEquals('200 OK', $response->headers['Status']);
		$this->assertContains('Not Clicked', $response->content);
		$this->assertContains('Method: POST', $response->content);

		// Now test a proper response
		$response = $this->sendRequest('POST', '/form_test/test_post', array('thing' => 'test_value_here', 'Submit' => 'Submit', SILK_FORM_VAR => 'blah'));
		$this->assertEquals('200 OK', $response->headers['Status']);
		$this->assertContains('Clicked: Submit', $response->content);
		$this->assertContains('Method: POST', $response->content);
		$this->assertContains('value="test_value_here"', $response->content);
		$this->assertNotContains('name="_method"', $response->content);

		// And a PUT for good measure
		// (can't rely on POST because this isn't going through rack)
		$response = $this->sendRequest('PUT', '/form_test/test_post', array('thing' => 'test_value_here', 'Submit' => 'Submit', SILK_FORM_VAR => 'blah', '_method' => 'put'));
		$this->assertEquals('200 OK', $response->headers['Status']);
		$this->assertContains('Clicked: Submit', $response->content);
		$this->assertContains('Method: PUT', $response->content);
		$this->assertContains('value="test_value_here"', $response->content);
		$this->assertContains('name="_method"', $response->content);
		$this->assertContains('value="PUT"', $response->content);
	}
}

class FormTestController extends \silk\action\Controller
{
	function test_post()
	{
		$request = request();
		$form = new \silk\form\Form('blah', array('method' => $request->requestMethod()));

		$form->addField('TextBox', 'thing', array());
		$form->addButton('Submit');

		if ($form->isPosted())
		{
			$form->fillFields();
			echo "Clicked: {$form->getClickedButton()}\n";
			echo "Form: {$form->render()}\n";
		}
		else
		{
			echo "Not Clicked\n";
		}

		echo "Method: " . $request->requestMethod() . "\n";
	}
}

# vim:ts=4 sw=4 noet
