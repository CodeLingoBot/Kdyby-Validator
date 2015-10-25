<?php

/**
 * Test: Kdyby\Validator\DI\ValidatorExtension.
 *
 * @testCase Kdyby\Validator\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Validator
 */

namespace KdybyTests\Validator;

use Kdyby;
use Nette;
use Symfony;
use Symfony\Component\Validator\Constraints as Assert;
use Tester;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class ExtensionTest extends Tester\TestCase
{

	/**
	 * @return Nette\DI\Container
	 */
	public function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . md5(time()))));
		$config->addConfig(__DIR__ . '/../nette-reset.neon', !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22');

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer();

		/** @var Symfony\Component\Validator\Validator\ValidatorInterface $validator */
		$validator = $container->getByType('Symfony\Component\Validator\Validator\ValidatorInterface');
		Tester\Assert::true($validator instanceof Symfony\Component\Validator\Validator\RecursiveValidator);

		$article = new ArticleMock();

		/** @var Symfony\Component\Validator\ConstraintViolationInterface[] $violations */
		$violations = $validator->validate($article);
		Tester\Assert::same(1, count($violations));
		Tester\Assert::same('This value should not be null.', $violations[0]->getMessage());

		$article->title = "Nette Framework + Symfony/Validator";

		/** @var Symfony\Component\Validator\ConstraintViolationInterface[] $violations */
		$violations = $validator->validate($article);
		Tester\Assert::same(0, count($violations));
	}



	public function testConstraintValidatorFactory()
	{
		$container = $this->createContainer();

		$factory = $container->getByType('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');

		// Validator without dependeny (created without DIC).
		Tester\Assert::type('Symfony\Component\Validator\Constraints\BlankValidator', $factory->getInstance(new \Symfony\Component\Validator\Constraints\Blank()));

		// ExpressionValidator (requires a special fix).
		Tester\Assert::type('Symfony\Component\Validator\Constraints\ExpressionValidator', $factory->getInstance(new \Symfony\Component\Validator\Constraints\Expression(array('expression' => ''))));

		// Custom validator with dependency (haa to be created by DIC).
		Tester\Assert::type('KdybyTests\ValidatorMock\FooConstraintValidator', $factory->getInstance(new \KdybyTests\ValidatorMock\FooConstraint()));
	}

}


class ArticleMock extends Nette\Object
{

	/**
	 * @Assert\NotNull()
	 * @var string
	 */
	public $title;

}

\run(new ExtensionTest());
