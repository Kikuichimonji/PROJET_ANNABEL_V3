<?php

namespace App\Tests\Functional;

use App\Entity\Cabinet;
use App\Entity\Patient;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Filet de securite fonctionnel ajoute avant la migration Symfony 5.1 -> 7.x.
 * Sert d'oracle stable a chaque etape : le login passe par $client->loginUser(),
 * qui ne depend pas de l'implementation Guard vs Authenticator Manager.
 */
class SmokeTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private Cabinet $cabinet;
    private Utilisateur $admin;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $cabinet = new Cabinet();
        $cabinet->setLibelle('Cabinet Test');
        $this->entityManager->persist($cabinet);

        $admin = new Utilisateur();
        $admin->setUsername('smoke_admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'smoke-test-password'));
        $this->entityManager->persist($admin);

        $patient = new Patient();
        $patient->setNom('Test');
        $patient->setPrenom('Smoke');
        $patient->setDateNaissance(new \DateTime('1990-01-01'));
        $patient->setUtilisateur($admin);
        $patient->addCabinet($cabinet);
        $this->entityManager->persist($patient);

        $this->entityManager->flush();

        $this->cabinet = $cabinet;
        $this->admin = $admin;
        $this->client = $client;
    }

    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testAuthenticatedRoutesAreAccessible(): void
    {
        $this->client->loginUser($this->admin);
        $cabinetId = $this->cabinet->getId();

        $this->client->request('GET', '/accueil');
        $this->assertResponseRedirects();

        $this->client->request('GET', '/accueil/' . $cabinetId);
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/cabinet');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/utilisateur');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/files');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/patient/' . $cabinetId);
        $this->assertResponseIsSuccessful();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        unset($this->entityManager);
    }
}
