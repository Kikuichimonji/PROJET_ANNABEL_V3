<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PatientRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 50)]
    private $nom;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $prenom;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $sexe;

    #[ORM\Column(type: 'date', nullable: true)]
    private $dateNaissance;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private $adresse;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private $codePostal;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private $ville;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private $numFixe;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private $numPortable;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $email;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $profession;

    #[ORM\Column(type: 'text', nullable: true)]
    private $loisir;

    #[ORM\OneToMany(targetEntity: Files::class, mappedBy: 'patient', cascade: ['remove'])]
    private $files;

    #[ORM\Column(type: 'datetime')]
    private $dateCreation;

    #[ORM\OneToMany(targetEntity: Consultation::class, mappedBy: 'patient', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['date_consult' => 'ASC'])]
    private $consultations;

    #[ORM\ManyToMany(targetEntity: Cabinet::class, inversedBy: 'patients', cascade: ['persist'])]
    private $cabinet;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antTete;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antOrl;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antOphtalmo;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antAuditif;

    #[ORM\Column(type: 'text', nullable: true)]
    private $AntDent;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antPulmo;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antCardia;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antDigest;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antUrin;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antGyneco;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antEndoc;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antDermato;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antFamille;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antTrauma;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antOpe;

    #[ORM\Column(type: 'text', nullable: true)]
    private $antPriseMedic;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'patients')]
    #[ORM\JoinColumn(nullable: false)]
    private $utilisateur;


    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->consultations = new ArrayCollection();
        $this->cabinet = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getNumFixe(): ?string
    {
        return $this->numFixe;
    }

    public function setNumFixe(?string $numFixe): self
    {
        $this->numFixe = $numFixe;

        return $this;
    }

    public function getNumPortable(): ?string
    {
        return $this->numPortable;
    }

    public function setNumPortable(?string $numPortable): self
    {
        $this->numPortable = $numPortable;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): self
    {
        $this->profession = $profession;

        return $this;
    }

    public function getLoisir(): ?string
    {
        return $this->loisir;
    }

    public function setLoisir(?string $loisir): self
    {
        $this->loisir = $loisir;

        return $this;
    }
    public function getLastConsult(): ?\DateTimeInterface
    {
        return $this->lastConsult;
    }

    public function setLastConsult(\DateTimeInterface $lastConsult): self
    {
        $this->lastConsult = $lastConsult;

        return $this;
    }

    /**
     * @return Collection|Files[]
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(Files $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files[] = $file;
            $file->setPatient($this);
        }

        return $this;
    }

    public function removeFile(Files $file): self
    {
        if ($this->files->contains($file)) {
            $this->files->removeElement($file);
            // set the owning side to null (unless already changed)
            if ($file->getPatient() === $this) {
                $file->setPatient(null);
            }
        }

        return $this;
    }


    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * @return Collection|Consultation[]
     */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): self
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations[] = $consultation;
            $consultation->setPatient($this);
        }

        return $this;
    }

    public function removeConsultation(Consultation $consultation): self
    {
        if ($this->consultations->contains($consultation)) {
            $this->consultations->removeElement($consultation);
            // set the owning side to null (unless already changed)
            if ($consultation->getPatient() === $this) {
                $consultation->setPatient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Cabinet[]
     */
    public function getCabinet(): Collection
    {
        return $this->cabinet;
    }

    public function addCabinet(Cabinet $cabinet): self
    {
        if (!$this->cabinet->contains($cabinet)) {
            $this->cabinet[] = $cabinet;
        }

        return $this;
    }

    public function removeCabinet(Cabinet $cabinet): self
    {
        if ($this->cabinet->contains($cabinet)) {
            $this->cabinet->removeElement($cabinet);
        }

        return $this;
    }

    public function getAntTete(): ?string
    {
        return $this->antTete;
    }

    public function setAntTete(?string $antTete): self
    {
        $this->antTete = $antTete;

        return $this;
    }

    public function getAntOrl(): ?string
    {
        return $this->antOrl;
    }

    public function setAntOrl(?string $antOrl): self
    {
        $this->antOrl = $antOrl;

        return $this;
    }

    public function getAntOphtalmo(): ?string
    {
        return $this->antOphtalmo;
    }

    public function setAntOphtalmo(?string $antOphtalmo): self
    {
        $this->antOphtalmo = $antOphtalmo;

        return $this;
    }

    public function getAntAuditif(): ?string
    {
        return $this->antAuditif;
    }

    public function setAntAuditif(?string $antAuditif): self
    {
        $this->antAuditif = $antAuditif;

        return $this;
    }

    public function getAntDent(): ?string
    {
        return $this->AntDent;
    }

    public function setAntDent(?string $AntDent): self
    {
        $this->AntDent = $AntDent;

        return $this;
    }

    public function getAntPulmo(): ?string
    {
        return $this->antPulmo;
    }

    public function setAntPulmo(?string $antPulmo): self
    {
        $this->antPulmo = $antPulmo;

        return $this;
    }

    public function getAntCardia(): ?string
    {
        return $this->antCardia;
    }

    public function setAntCardia(?string $antCardia): self
    {
        $this->antCardia = $antCardia;

        return $this;
    }

    public function getAntDigest(): ?string
    {
        return $this->antDigest;
    }

    public function setAntDigest(?string $antDigest): self
    {
        $this->antDigest = $antDigest;

        return $this;
    }

    public function getAntUrin(): ?string
    {
        return $this->antUrin;
    }

    public function setAntUrin(?string $antUrin): self
    {
        $this->antUrin = $antUrin;

        return $this;
    }

    public function getAntGyneco(): ?string
    {
        return $this->antGyneco;
    }

    public function setAntGyneco(?string $antGyneco): self
    {
        $this->antGyneco = $antGyneco;

        return $this;
    }

    public function getAntEndoc(): ?string
    {
        return $this->antEndoc;
    }

    public function setAntEndoc(?string $antEndoc): self
    {
        $this->antEndoc = $antEndoc;

        return $this;
    }

    public function getAntDermato(): ?string
    {
        return $this->antDermato;
    }

    public function setAntDermato(?string $antDermato): self
    {
        $this->antDermato = $antDermato;

        return $this;
    }

    public function getAntFamille(): ?string
    {
        return $this->antFamille;
    }

    public function setAndFamille(?string $antFamille): self
    {
        $this->antFamille = $antFamille;

        return $this;
    }

    public function getAntTrauma(): ?string
    {
        return $this->antTrauma;
    }

    public function setAntTrauma(?string $antTrauma): self
    {
        $this->antTrauma = $antTrauma;

        return $this;
    }

    public function getAntOpe(): ?string
    {
        return $this->antOpe;
    }

    public function setAntOpe(?string $antOpe): self
    {
        $this->antOpe = $antOpe;

        return $this;
    }

    public function getAntPriseMedic(): ?string
    {
        return $this->antPriseMedic;
    }

    public function setAntPriseMedic(?string $antPriseMedic): self
    {
        $this->antPriseMedic = $antPriseMedic;

        return $this;
    }

    public function getLastConsultationDate()
    {
        $lastConsultation = $this->getConsultations()->last();
        if (!$lastConsultation) {
            return null;
        }

        $date = date_format($lastConsultation->getDateConsult(), "d/m/Y");
        $cabinet = $lastConsultation->getCabinet();
        if ($cabinet) {
            $date .= " à : " . $cabinet->getLibelle();
        }

        return $date;
    }

    public function setAntFamille(?string $antFamille): self
    {
        $this->antFamille = $antFamille;

        return $this;
    }

    public function getUtilisateur(): ?utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }


}
