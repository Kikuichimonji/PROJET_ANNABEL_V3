<?php

namespace App\Form;

use App\Entity\Cabinet;
use App\Entity\Patient;
use App\Entity\Utilisateur;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class PatientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        
        $builder
            ->add('utilisateur',EntityType::class,[
                'class' => Utilisateur::class,
                'choice_label' => 'id',
                "attr" => [
                    "class" => "hidden",
                ],
                "label" => false,    
                "placeholder" => ""   
            ])
            ->add('nom', TextType::class,[
                "required" => false,
            ])
            ->add('prenom', TextType::class,[
                "required" => false,
                "label" => "Prénom",
            ])
            ->add('sexe', TextType::class,[
                "required" => false,
            ])
            ->add('dateNaissance',DateType::class,[
                'widget' => 'single_text',
                "label" => "Date de naissance",
            ])
            ->add('adresse', TextType::class,[
                "required" => false,
            ])
            ->add('codePostal', TextType::class,[
                "required" => false,
            ])
            ->add('ville', TextType::class,[
                "required" => false,
            ])
            ->add('numFixe', TextType::class,[
                "required" => false,
                "label" => "Numéro Fixe"
            ])
            ->add('numPortable', TextType::class,[
                "required" => false,
                "label" => "Numéro Portable"
            ])
            ->add('email', TextType::class,[
                "required" => false
            ])
            ->add('profession', TextType::class,[
                "required" => false
            ])
            ->add('loisir',TextareaType::class,[
                "required" => false,
                "label" => "Loisirs",
            ])
            ->add('antTete',TextareaType::class,[
                "required" => false,
                "label" => "Tête"
            ])
            ->add('antOrl',TextareaType::class,[
                "required" => false,
                "label" => "ORL"
            ])
            ->add('antOphtalmo',TextareaType::class,[
                "required" => false,
                "label" => "Ophtalmo"
            ])
            ->add('antAuditif',TextareaType::class,[
                "required" => false,
                "label" => "Auditif"
            ])
            ->add('antDent',TextareaType::class,[
                "required" => false,
                "label" => "Dentaire"
            ])
            ->add('antPulmo',TextareaType::class,[
                "required" => false,
                "label" => "Pulmonaire"
            ])
            ->add('antCardia',TextareaType::class,[
                "required" => false,
                "label" => "Cardiaque/Circulatoire"
            ])
            ->add('antDigest',TextareaType::class,[
                "required" => false,
                "label" => "Digestif"
            ])
            ->add('antUrin',TextareaType::class,[
                "required" => false,
                "label" => "Urinaire"
            ])
            ->add('antGyneco',TextareaType::class,[
                "required" => false,
                "label" => "Gynécologique"
            ])
            ->add('antEndoc',TextareaType::class,[
                "required" => false,
                "label" => "Endocrine"
            ])
            ->add('antDermato',TextareaType::class,[
                "required" => false,
                "label" => "Dermatologique"
            ])
            ->add('antFamille',TextareaType::class,[
                "required" => false,
                "label" => "Familiaux"
            ])
            ->add('antTrauma',TextareaType::class,[
                "required" => false,
                "label" => false
            ])
            ->add('antOpe',TextareaType::class,[
                "required" => false,
                "label" => false
            ])
            ->add('antPriseMedic',TextareaType::class,[
                "required" => false,
                "label" => false
            ])
            ->add('cabinet',EntityType::class,[
                'class' => Cabinet::class,
                'choice_label' => 'id',
                'multiple'  => true,
                'label' => false,
                "attr" => ["class" => "hidden"],
            ])

            ->add('consultations', CollectionType::class, [
                "entry_type" => ConsultationType::class,
                "allow_delete" => true,
                "entry_options" => [
                    "label" => false,
                ],
                "label" => false,
                "allow_add" => true,
                "delete_empty" => true,
                'by_reference' => false,
            ])
            
            ->add("Enregistrer",SubmitType::class,[
                'attr' => ['class' => 'btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patient::class,
        ]);

    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {

    }
}
