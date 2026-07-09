<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Patient;

/**
 * Calcule les statistiques de la page /statistiques a partir de listes de
 * Patient/Consultation deja chargees (pas de requete ici) : le volume de
 * donnees d'un cabinet d'osteopathie reste petit, le calcul en PHP evite
 * d'avoir a ecrire des fonctions DQL/SQLite non portables (extraction de
 * mois/annee, agregation groupee par patient).
 */
class StatistiquesService
{
    /**
     * @param Patient[]      $patients
     * @param Consultation[] $consultations
     */
    public function calculer(array $patients, array $consultations, ?\DateTimeInterface $maintenant = null): array
    {
        $maintenant = $maintenant ?? new \DateTimeImmutable();
        $anneeCourante = (int) $maintenant->format('Y');
        $moisCourant = (int) $maintenant->format('n');

        $consultationsCeMois = 0;
        $consultationsCetteAnnee = 0;
        $chiffreAffairesTotal = 0.0;
        $chiffreAffairesCeMois = 0.0;
        $totalImpaye = 0.0;
        $premiereConsultationParPatient = [];
        $consultationsParMoisCle = [];
        $repartitionPaiement = [];

        foreach ($consultations as $consultation) {
            $date = $consultation->getDateConsult();
            if (!$date) {
                continue;
            }
            $montant = (float) ($consultation->getMontant() ?? 0);
            $anneeConsult = (int) $date->format('Y');
            $moisConsult = (int) $date->format('n');

            $chiffreAffairesTotal += $montant;

            if ($anneeConsult === $anneeCourante) {
                $consultationsCetteAnnee++;
                if ($moisConsult === $moisCourant) {
                    $consultationsCeMois++;
                    $chiffreAffairesCeMois += $montant;
                }
            }

            $moyenPaiement = $consultation->getMoyenPaiement();
            $libellePaiement = $moyenPaiement && $moyenPaiement->getLibelle() ? $moyenPaiement->getLibelle() : 'Non renseigné';
            if (!isset($repartitionPaiement[$libellePaiement])) {
                $repartitionPaiement[$libellePaiement] = ['count' => 0, 'montant' => 0.0];
            }
            $repartitionPaiement[$libellePaiement]['count']++;
            $repartitionPaiement[$libellePaiement]['montant'] += $montant;

            if ($libellePaiement === 'Non payé') {
                $totalImpaye += $montant;
            }

            $patient = $consultation->getPatient();
            if ($patient && $patient->getId() !== null) {
                $patientId = $patient->getId();
                if (!isset($premiereConsultationParPatient[$patientId]) || $date < $premiereConsultationParPatient[$patientId]) {
                    $premiereConsultationParPatient[$patientId] = $date;
                }
            }

            $cleMois = $date->format('Y-m');
            $consultationsParMoisCle[$cleMois] = ($consultationsParMoisCle[$cleMois] ?? 0) + 1;
        }

        $nouveauxPatientsCeMois = 0;
        $nouveauxPatientsCetteAnnee = 0;
        foreach ($premiereConsultationParPatient as $datePremiereConsultation) {
            $annee = (int) $datePremiereConsultation->format('Y');
            $mois = (int) $datePremiereConsultation->format('n');
            if ($annee === $anneeCourante) {
                $nouveauxPatientsCetteAnnee++;
                if ($mois === $moisCourant) {
                    $nouveauxPatientsCeMois++;
                }
            }
        }

        $consultationsParMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $curseur = (clone $maintenant)->modify("-{$i} months");
            $cle = $curseur->format('Y-m');
            $consultationsParMois[] = [
                'label' => $this->formaterMoisFrancais($curseur),
                'count' => $consultationsParMoisCle[$cle] ?? 0,
            ];
        }

        $sommeAges = 0;
        $nbAges = 0;
        $tranchesAge = ['enfants' => 0, 'adultes' => 0, 'seniors' => 0];
        $repartitionSexe = ['hommes' => 0, 'femmes' => 0, 'nonRenseigne' => 0];

        foreach ($patients as $patient) {
            $naissance = $patient->getDateNaissance();
            if ($naissance) {
                $age = $naissance->diff($maintenant)->y;
                $sommeAges += $age;
                $nbAges++;
                if ($age < 18) {
                    $tranchesAge['enfants']++;
                } elseif ($age < 65) {
                    $tranchesAge['adultes']++;
                } else {
                    $tranchesAge['seniors']++;
                }
            }

            $sexe = strtolower(trim((string) $patient->getSexe()));
            if ($sexe === 'homme') {
                $repartitionSexe['hommes']++;
            } elseif ($sexe === 'femme') {
                $repartitionSexe['femmes']++;
            } else {
                $repartitionSexe['nonRenseigne']++;
            }
        }

        return [
            'totalPatients' => count($patients),
            'totalConsultations' => count($consultations),
            'consultationsCeMois' => $consultationsCeMois,
            'consultationsCetteAnnee' => $consultationsCetteAnnee,
            'nouveauxPatientsCeMois' => $nouveauxPatientsCeMois,
            'nouveauxPatientsCetteAnnee' => $nouveauxPatientsCetteAnnee,
            'consultationsParMois' => $consultationsParMois,
            'chiffreAffairesTotal' => $chiffreAffairesTotal,
            'chiffreAffairesCeMois' => $chiffreAffairesCeMois,
            'totalImpaye' => $totalImpaye,
            'repartitionPaiement' => $repartitionPaiement,
            'ageMoyen' => $nbAges > 0 ? $sommeAges / $nbAges : null,
            'tranchesAge' => $tranchesAge,
            'repartitionSexe' => $repartitionSexe,
        ];
    }

    private function formaterMoisFrancais(\DateTimeInterface $date): string
    {
        $mois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return $mois[(int) $date->format('n')] . ' ' . $date->format('Y');
    }
}
