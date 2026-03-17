<?php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
private $hasher;

public function __construct(UserPasswordHasherInterface $hasher)
{
$this->hasher = $hasher;
}

public function load(ObjectManager $manager): void
{

$user = new User();
$user->setEmail('harel.mathis@gmail.com');
$user->setFirstName('Mathis');
$user->setLastName('Harel');


$password = $this->hasher->hashPassword($user, '123456');
$user->setPassword($password);


$user->setRoles(['ROLE_ADMIN']);

$manager->persist($user);
$manager->flush();
}
}
