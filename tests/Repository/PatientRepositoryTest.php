<?php

namespace App\Tests\Repository;

use App\Data\SearchData;
use App\Entity\Cabinet;
use App\Entity\Patient;
use App\Entity\Utilisateur;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Verrouille le comportement de PatientRepository::getBySearch() : la recherche
 * texte et le filtre par cabinet doivent se combiner en ET, jamais l'un absorber
 * l'autre (logique de requete construite dynamiquement, fragile).
 */
class PatientRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PatientRepository $repository;
    private Cabinet $cabinetA;
    private Cabinet $cabinetB;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Patient::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $utilisateur = new Utilisateur();
        $utilisateur->setUsername('praticien_test');
        $utilisateur->setRoles(['ROLE_USER']);
        $utilisateur->setPassword('not-used-in-this-test');
        $this->entityManager->persist($utilisateur);

        $this->cabinetA = new Cabinet();
        $this->cabinetA->setLibelle('Cabinet A');
        $this->entityManager->persist($this->cabinetA);

        $this->cabinetB = new Cabinet();
        $this->cabinetB->setLibelle('Cabinet B');
        $this->entityManager->persist($this->cabinetB);

        $this->createPatient('Dupont', 'Alice', $utilisateur, [$this->cabinetA]);
        $this->createPatient('Martin', 'Bob', $utilisateur, [$this->cabinetA, $this->cabinetB]);
        $this->createPatient('Durand', 'Chloe', $utilisateur, [$this->cabinetB]);
        $this->createPatient('Lajeunesse', 'Élise', $utilisateur, [$this->cabinetA]);

        $this->entityManager->flush();
    }

    private function createPatient(string $nom, string $prenom, Utilisateur $utilisateur, array $cabinets): Patient
    {
        $patient = new Patient();
        $patient->setNom($nom);
        $patient->setPrenom($prenom);
        $patient->setDateNaissance(new \DateTime('1990-01-01'));
        $patient->setUtilisateur($utilisateur);
        foreach ($cabinets as $cabinet) {
            $patient->addCabinet($cabinet);
        }
        $this->entityManager->persist($patient);

        return $patient;
    }

    private function names(iterable $result): array
    {
        $names = [];
        foreach ($result as $patient) {
            $names[] = $patient->getNom();
        }
        sort($names);

        return $names;
    }

    public function testTextSearchMatchesOnNom(): void
    {
        $data = new SearchData();
        $data->q = 'Dupont';

        $this->assertSame(['Dupont'], $this->names($this->repository->getBySearch($data)));
    }

    public function testTextSearchMatchesOnPrenom(): void
    {
        $data = new SearchData();
        $data->q = 'Chloe';

        $this->assertSame(['Durand'], $this->names($this->repository->getBySearch($data)));
    }

    public function testEmptySearchReturnsEveryone(): void
    {
        $data = new SearchData();

        $this->assertSame(['Dupont', 'Durand', 'Lajeunesse', 'Martin'], $this->names($this->repository->getBySearch($data)));
    }

    public function testTextSearchIgnoresAccents(): void
    {
        // "elise" (sans accent) doit trouver "Élise" : SQLite n'a pas de collation
        // accent-insensible comme MySQL, la comparaison est normalisee en PHP.
        $data = new SearchData();
        $data->q = 'elise';

        $this->assertSame(['Lajeunesse'], $this->names($this->repository->getBySearch($data)));
    }

    public function testCabinetFilterAppliesAsAndNotAbsorbedByTextSearchOr(): void
    {
        // Recherche vide + filtre sur le cabinet A seul : ne doit renvoyer que les
        // patients de A (Alice, Bob), jamais Chloe (cabinet B uniquement) — c'est
        // le coeur du risque identifie (clause andWhere construite a la main a la
        // suite d'une chaine de orWhere pour le texte).
        $data = new SearchData();
        $data->cabinets = [$this->cabinetA];

        $this->assertSame(['Dupont', 'Lajeunesse', 'Martin'], $this->names($this->repository->getBySearch($data)));
    }

    public function testCabinetFilterCombinedWithTextSearch(): void
    {
        // Bob est dans A+B ; chercher "Bob" tout en filtrant sur le cabinet B doit
        // le trouver (il y est bien), mais filtrer sur un texte qui ne matche
        // aucun patient de B ne doit rien renvoyer meme si le texte existe ailleurs.
        $data = new SearchData();
        $data->q = 'Martin';
        $data->cabinets = [$this->cabinetB];

        $this->assertSame(['Martin'], $this->names($this->repository->getBySearch($data)));

        $data2 = new SearchData();
        $data2->q = 'Dupont';
        $data2->cabinets = [$this->cabinetB];

        $this->assertSame([], $this->names($this->repository->getBySearch($data2)));
    }

    public function testMultipleCabinetsFilterIsOrWithinTheCabinetGroup(): void
    {
        $data = new SearchData();
        $data->cabinets = [$this->cabinetA, $this->cabinetB];

        $this->assertSame(['Dupont', 'Durand', 'Lajeunesse', 'Martin'], $this->names($this->repository->getBySearch($data)));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        unset($this->entityManager);
    }
}
