<?php

namespace App\Tests\Functional;

use App\Entity\Cabinet;
use App\Entity\Files;
use App\Entity\Patient;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Couvre les 3 scenarios de PatientController::deletePatient() :
 * - suppression complete (0 consultation) : fichiers et liens cabinet doivent
 *   etre nettoyes, pas laisses orphelins (bug corrige : la suppression passait
 *   auparavant par une requete DQL brute qui contournait l'EntityManager).
 * - retrait d'un patient d'un cabinet precis.
 * - retrait avec un identifiant de cabinet invalide : doit rediriger proprement,
 *   pas planter (bug corrige : $cabinet[0] sur un tableau vide).
 */
class PatientDeletionTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;
    private Utilisateur $admin;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->admin = new Utilisateur();
        $this->admin->setUsername('admin_test');
        $this->admin->setRoles(['ROLE_ADMIN']);
        $this->admin->setPassword($passwordHasher->hashPassword($this->admin, 'test-password'));
        $this->entityManager->persist($this->admin);

        $this->entityManager->flush();
    }

    private function createPatientWithCabinets(array $cabinets): Patient
    {
        $patient = new Patient();
        $patient->setNom('TestPatient');
        $patient->setPrenom('Jean');
        $patient->setDateNaissance(new \DateTime('1990-01-01'));
        $patient->setUtilisateur($this->admin);
        foreach ($cabinets as $cabinet) {
            $patient->addCabinet($cabinet);
        }
        $this->entityManager->persist($patient);

        return $patient;
    }

    public function testFullDeleteCleansUpFilesAndCabinetLinks(): void
    {
        $cabinet = new Cabinet();
        $cabinet->setLibelle('Cabinet Test');
        $this->entityManager->persist($cabinet);

        $patient = $this->createPatientWithCabinets([$cabinet]);

        $file = new Files();
        $file->setPath('uploads/document-test.pdf');
        $file->setPatient($patient);
        $file->setDateCreation(new \DateTime());
        $this->entityManager->persist($file);

        $this->entityManager->flush();
        $patientId = $patient->getId();
        // Comme en production, chaque requete part d'un EntityManager "frais" :
        // on force la reinitialisation pour que les collections (files, cabinet)
        // soient rehydratees depuis la base au lieu de rester les objets PHP
        // vides construits par le fixture, ce qui masquerait le bug de cascade.
        $this->entityManager->clear();

        $this->client->loginUser($this->admin);
        $this->client->request('GET', '/DeletePatient/' . $patientId);
        $this->assertResponseRedirects();

        // Le patient, son fichier et son lien vers le cabinet doivent tous avoir disparu.
        $this->entityManager->clear();
        $this->assertNull($this->entityManager->find(Patient::class, $patientId));

        $connection = $this->entityManager->getConnection();
        $remainingFiles = $connection->fetchOne('SELECT COUNT(*) FROM files WHERE patient_id = ?', [$patientId]);
        $this->assertSame(0, (int) $remainingFiles, 'Le fichier du patient supprime ne doit pas rester orphelin');

        $remainingLinks = $connection->fetchOne('SELECT COUNT(*) FROM patient_cabinet WHERE patient_id = ?', [$patientId]);
        $this->assertSame(0, (int) $remainingLinks, 'Le lien patient/cabinet ne doit pas rester orphelin');
    }

    public function testRemoveFromSpecificCabinetKeepsPatientInOthers(): void
    {
        $cabinetA = new Cabinet();
        $cabinetA->setLibelle('Cabinet A');
        $this->entityManager->persist($cabinetA);

        $cabinetB = new Cabinet();
        $cabinetB->setLibelle('Cabinet B');
        $this->entityManager->persist($cabinetB);

        $patient = $this->createPatientWithCabinets([$cabinetA, $cabinetB]);
        $this->entityManager->flush();
        $patientId = $patient->getId();
        $cabinetAId = $cabinetA->getId();
        $cabinetBId = $cabinetB->getId();
        $this->entityManager->clear();

        $this->client->loginUser($this->admin);
        $this->client->request('GET', "/DeletePatient/{$cabinetAId}/{$patientId}");
        $this->assertResponseRedirects();

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(Patient::class, $patientId);
        $this->assertNotNull($reloaded, 'Le patient doit toujours exister');

        $cabinetIds = array_map(static fn (Cabinet $c) => $c->getId(), $reloaded->getCabinet()->toArray());
        $this->assertNotContains($cabinetAId, $cabinetIds, 'Le patient ne doit plus etre lie au cabinet A');
        $this->assertContains($cabinetBId, $cabinetIds, 'Le patient doit rester lie au cabinet B');
    }

    public function testRemoveWithInvalidCabinetIdRedirectsInsteadOfCrashing(): void
    {
        $cabinet = new Cabinet();
        $cabinet->setLibelle('Cabinet Test');
        $this->entityManager->persist($cabinet);

        $patient = $this->createPatientWithCabinets([$cabinet]);
        $this->entityManager->flush();
        $patientId = $patient->getId();
        $this->entityManager->clear();

        $this->client->loginUser($this->admin);
        $this->client->request('GET', "/DeletePatient/999999/{$patientId}");

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('err=3', $location);

        // Le patient ne doit pas avoir ete affecte par la tentative ratee.
        $this->entityManager->clear();
        $this->assertNotNull($this->entityManager->find(Patient::class, $patientId));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        unset($this->entityManager);
    }
}
