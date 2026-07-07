<?php

namespace App\Form;

use App\Entity\Cabinet;
use App\Entity\Patient;
use App\Entity\Utilisateur;
use App\Entity\Consultation;
use App\Entity\MoyenPaiement;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_consult',DateType::class,[
                'widget' => 'single_text',
                "label" => "Date de consultation",
                "attr" => ["class" => "uk-input consultReq"],
            ])
            ->add('anamnese',TextareaType::class,[
                "required" => false,
                "attr" => ["class" => "uk-textarea consultReq"],
                "label" => "Anamnèse"
                ])
            ->add('test',TextareaType::class,[
                "required" => false,
                "attr" => ["class" => "uk-textarea consultReq"],
                ])
            ->add('traitement',TextareaType::class,[
                "required" => false,
                "attr" => ["class" => "uk-textarea consultReq"],
                ])
            ->add('conseil',TextareaType::class,[
                "required" => false,
                "attr" => ["class" => "uk-textarea consultReq"],
                ])
            ->add('note',TextareaType::class,[
                "required" => false,
                "attr" => ["class" => "uk-textarea consultReq"],
                ])
            ->add('moyen_paiement',EntityType::class,[
                    "attr" => ["class" => "consultRadioButton"],
                    'class' => MoyenPaiement::class,
                    'choice_label' => 'libelle',
                    'expanded' => true,
                    "label" => "Moyen de paiement",
                    ])
                    
            ->add('montant',IntegerType::class,[
                "required" => false,
                "attr" => ["class" => "uk-input consultReq"],
                ])
            ->add('numero_cheque',TextType::class,[
                "attr" => ["class" => "uk-input"],
                "required" => false,
                "label" => "Numéro de chèque"
                ])
            ->add('patient',EntityType::class,[
                'class' => Patient::class,
                'choice_label' => 'id',
                "attr" => ["class" => "hidden"],
                "label" => false
            
            ])
            ->add('utilisateur',EntityType::class,[
                'class' => Utilisateur::class,
                'choice_label' => 'id',
                "attr" => ["class" => "hidden"],
                "label" => false,       
            ])
            ->add('cabinet',EntityType::class,[
                'class' => Cabinet::class,
                'choice_label' => 'id',
                "attr" => ["class" => "hidden"],
                "label" => false,       
            ])
            ->add('texte', TextType::class,[
                "attr" => ["class" => "hidden"],
                "label" => false,
                "required" => false,
            ])
            ->add('id',IntegerType::class,[
                "attr" => ["class" => "hidden idConsult"],
                "label" => false,
                "required" => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}
