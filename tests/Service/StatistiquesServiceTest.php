<?php

namespace App\Tests\Service;

use App\Entity\Consultation;
use App\Entity\MoyenPaiement;
use App\Entity\Patient;
use App\Service\StatistiquesService;
use PHPUnit\Framework\TestCase;

/**
 * Verrouille le calcul de la page /statistiques : aucune requete DB ici, les
 * entites sont construites a la main (id assigne par reflection, comme un
 * Patient/Consultation persiste le serait) pour tester la logique de calcul
 * en isolation, sans dependre de Doctrine/SQLite.
 */
class StatistiquesServiceTest extends TestCase
{
    private StatistiquesService $service;
    private \DateTimeImmutable $maintenant;

    protected function setUp(): void
    {
        $this->service = new StatistiquesService();
        // Date fixe pour que les tests ne dependent pas du jour d'execution
        $this->maintenant = new \DateTimeImmutable('2026-07-15');
    }

    private function setId(object $entity, int $id): void
    {
        $ref = new \ReflectionProperty($entity, 'id');
        $ref->setAccessible(true);
        $ref->setValue($entity, $id);
    }

    private function moyenPaiement(string $libelle): MoyenPaiement
    {
        $moyen = new MoyenPaiement();
        $moyen->setLibelle($libelle);

        return $moyen;
    }

    private function patient(int $id, string $sexe, \DateTimeInterface $naissance): Patient
    {
        $patient = new Patient();
        $this->setId($patient, $id);
        $patient->setNom('Nom' . $id);
        $patient->setPrenom('Prenom' . $id);
        $patient->setSexe($sexe);
        $patient->setDateNaissance($naissance);

        return $patient;
    }

    private function consultation(Patient $patient, string $date, float $montant, MoyenPaiement $moyen): Consultation
    {
        $consultation = new Consultation();
        $consultation->setPatient($patient);
        $consultation->setDateConsult(new \DateTime($date));
        $consultation->setMontant($montant);
        $consultation->setMoyenPaiement($moyen);

        return $consultation;
    }

    public function testComptesDeBase(): void
    {
        $especes = $this->moyenPaiement('Espèce');
        $p1 = $this->patient(1, 'Homme', new \DateTime('1990-01-01'));
        $p2 = $this->patient(2, 'femme', new \DateTime('2000-01-01'));

        $consultations = [
            $this->consultation($p1, '2026-07-10', 30, $especes),
            $this->consultation($p2, '2026-06-01', 30, $especes),
        ];

        $stats = $this->service->calculer([$p1, $p2], $consultations, $this->maintenant);

        $this->assertSame(2, $stats['totalPatients']);
        $this->assertSame(2, $stats['totalConsultations']);
    }

    public function testConsultationsCeMoisEtCetteAnnee(): void
    {
        $especes = $this->moyenPaiement('Espèce');
        $p1 = $this->patient(1, 'Homme', new \DateTime('1990-01-01'));

        $consultations = [
            $this->consultation($p1, '2026-07-10', 30, $especes), // ce mois
            $this->consultation($p1, '2026-01-05', 30, $especes), // cette annee, pas ce mois
            $this->consultation($p1, '2025-12-20', 30, $especes), // ni l'un ni l'autre
        ];

        $stats = $this->service->calculer([$p1], $consultations, $this->maintenant);

        $this->assertSame(1, $stats['consultationsCeMois']);
        $this->assertSame(2, $stats['consultationsCetteAnnee']);
    }

    public function testNouveauxPatientsBaseSurLaPremiereConsultation(): void
    {
        $especes = $this->moyenPaiement('Espèce');
        $p1 = $this->patient(1, 'Homme', new \DateTime('1990-01-01'));
        $p2 = $this->patient(2, 'femme', new \DateTime('1990-01-01'));

        $consultations = [
            // p1 : premiere consultation ce mois -> nouveau patient
            $this->consultation($p1, '2026-07-05', 30, $especes),
            // p2 : vue pour la premiere fois il y a longtemps, puis revue ce mois
            // -> ne doit PAS compter comme nouveau patient malgre la consultation ce mois-ci
            $this->consultation($p2, '2024-01-01', 30, $especes),
            $this->consultation($p2, '2026-07-06', 30, $especes),
        ];

        $stats = $this->service->calculer([$p1, $p2], $consultations, $this->maintenant);

        $this->assertSame(1, $stats['nouveauxPatientsCeMois']);
    }

    public function testConsultationsParMoisCouvre12MoisAvecLesMoisVidesAZero(): void
    {
        $especes = $this->moyenPaiement('Espèce');
        $p1 = $this->patient(1, 'Homme', new \DateTime('1990-01-01'));

        $consultations = [
            $this->consultation($p1, '2026-07-10', 30, $especes),
        ];

        $stats = $this->service->calculer([$p1], $consultations, $this->maintenant);

        $this->assertCount(12, $stats['consultationsParMois']);
        $this->assertSame('Août 2025', $stats['consultationsParMois'][0]['label']);
        $this->assertSame('Juillet 2026', $stats['consultationsParMois'][11]['label']);
        $this->assertSame(1, $stats['consultationsParMois'][11]['count']);
        $this->assertSame(0, $stats['consultationsParMois'][0]['count']);
    }

    public function testFinancesEtRepartitionParMoyenDePaiement(): void
    {
        $especes = $this->moyenPaiement('Espèce');
        $cheque = $this->moyenPaiement('Chèque');
        $nonPaye = $this->moyenPaiement('Non payé');
        $p1 = $this->patient(1, 'Homme', new \DateTime('1990-01-01'));

        $consultations = [
            $this->consultation($p1, '2026-07-01', 30, $especes),
            $this->consultation($p1, '2026-07-02', 40, $cheque),
            $this->consultation($p1, '2026-06-01', 25, $nonPaye),
        ];

        $stats = $this->service->calculer([$p1], $consultations, $this->maintenant);

        $this->assertSame(95.0, $stats['chiffreAffairesTotal']);
        $this->assertSame(70.0, $stats['chiffreAffairesCeMois']);
        $this->assertSame(25.0, $stats['totalImpaye']);
        $this->assertSame(1, $stats['repartitionPaiement']['Espèce']['count']);
        $this->assertSame(30.0, $stats['repartitionPaiement']['Espèce']['montant']);
        $this->assertSame(1, $stats['repartitionPaiement']['Non payé']['count']);
    }

    public function testAgeMoyenEtTranchesDage(): void
    {
        // au 2026-07-15 : 36 ans, 10 ans, 70 ans
        $p1 = $this->patient(1, 'Homme', new \DateTime('1990-01-01'));
        $p2 = $this->patient(2, 'femme', new \DateTime('2016-01-01'));
        $p3 = $this->patient(3, 'Homme', new \DateTime('1956-01-01'));

        $stats = $this->service->calculer([$p1, $p2, $p3], [], $this->maintenant);

        $this->assertEqualsWithDelta(38.67, $stats['ageMoyen'], 0.1);
        $this->assertSame(1, $stats['tranchesAge']['enfants']);
        $this->assertSame(1, $stats['tranchesAge']['adultes']);
        $this->assertSame(1, $stats['tranchesAge']['seniors']);
    }

    public function testRepartitionSexeEstInsensibleALaCasseEtGereLesValeursVides(): void
    {
        $p1 = $this->patient(1, 'Homme', new \DateTime('1990-01-01'));
        $p2 = $this->patient(2, 'femme', new \DateTime('1990-01-01')); // minuscule, comme en base reelle
        $p3 = $this->patient(3, '', new \DateTime('1990-01-01'));

        $stats = $this->service->calculer([$p1, $p2, $p3], [], $this->maintenant);

        $this->assertSame(1, $stats['repartitionSexe']['hommes']);
        $this->assertSame(1, $stats['repartitionSexe']['femmes']);
        $this->assertSame(1, $stats['repartitionSexe']['nonRenseigne']);
    }
}
