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

use \silk\test\TestCase;
use \silk\form\Form;

class FormTest extends TestCase
{
	public function testBasicForm()
	{
		$form = new Form('test', array('id' => 'the_id', 'class' => 'the_class'));
		$this->assertNotNull($form);
		$this->assertEquals($form->getId(), 'the_id');
		$this->assertEquals($form->getClass(), 'the_class');
		$this->assertEquals($form->getName(), 'test');
		$this->assertNull($form->getBlah());

		$form = new Form('test');
		$this->assertNotNull($form);
		$form = $form->setId('the_id')->setClass('the_class');
		$this->assertEquals($form->getId(), 'the_id');
		$this->assertEquals($form->getClass(), 'the_class');
		$this->assertEquals($form->getName(), 'test');
		$this->assertNull($form->getBlah());

		$result = $form->render();
		$this->assertContains('id="the_id"', $result);
		$this->assertContains('class="the_class"', $result);
		$this->assertContains('<form', $result);
		$this->assertContains('</form>', $result);
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
}

# vim:ts=4 sw=4 noet
