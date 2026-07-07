<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConsultationRepository::class)
 */
class Consultation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_consult;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $test;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $traitement;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $conseil;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $montant;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $numero_cheque;

    /**
     * @ORM\ManyToOne(targetEntity=Patient::class, inversedBy="consultations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $patient;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $anamnese;

    /**
     * @ORM\ManyToOne(targetEntity=MoyenPaiement::class, inversedBy="consultations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $moyen_paiement;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="consultations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $utilisateur;

    /**
     * @ORM\ManyToOne(targetEntity=Cabinet::class, inversedBy="consultations")
     */
    private $cabinet;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateConsult(): ?\DateTimeInterface
    {
        return $this->date_consult;
    }

    public function setDateConsult(\DateTimeInterface $date_consult): self
    {
        $this->date_consult = $date_consult;

        return $this;
    }

    public function getTest(): ?string
    {
        return $this->test;
    }

    public function setTest(?string $test): self
    {
        $this->test = $test;

        return $this;
    }

    public function getTraitement(): ?string
    {
        return $this->traitement;
    }

    public function setTraitement(?string $traitement): self
    {
        $this->traitement = $traitement;

        return $this;
    }

    public function getConseil(): ?string
    {
        return $this->conseil;
    }

    public function setConseil(?string $conseil): self
    {
        $this->conseil = $conseil;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(?float $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getNumeroCheque(): ?string
    {
        return $this->numero_cheque;
    }

    public function setNumeroCheque(?string $numero_cheque): self
    {
        $this->numero_cheque = $numero_cheque;

        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): self
    {
        $this->patient = $patient;

        return $this;
    }

    public function getAnamnese(): ?string
    {
        return $this->anamnese;
    }

    public function setAnamnese(?string $anamnese): self
    {
        $this->anamnese = $anamnese;

        return $this;
    }

    public function getMoyenPaiement(): ?MoyenPaiement
    {
        return $this->moyen_paiement;
    }

    public function setMoyenPaiement(?MoyenPaiement $moyen_paiement): self
    {
        $this->moyen_paiement = $moyen_paiement;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getTexte(){
        $texte = "Consultation du ".$this->date_consult->format("d/m/Y")." par ".$this->utilisateur->getUsername();
        if ($this->cabinet) {
            $texte .= " à : ".$this->cabinet->getLibelle();
        }

        return $texte;
    }

    public function setTexte(String $texte = null){
        $this->texte = $texte;

        return $this;
    }

    public function getCabinet(): ?Cabinet
    {
        return $this->cabinet;
    }

    public function setCabinet(?Cabinet $cabinet): self
    {
        $this->cabinet = $cabinet;

        return $this;
    }
}
