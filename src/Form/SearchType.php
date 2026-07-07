<?php

namespace App\Form;

use App\Entity\Cabinet;
use App\Data\SearchData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('q', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    "placeholder" => "Rechercher",
                    "class" => "uk-input"
                ]
            ])
            ->add("cabinets", EntityType::class, [
                "label" => false,
                "required" => false,
                "class" => Cabinet::class,
                'choice_label' => 'libelle',
                "multiple" => true,
                "expanded" => true,
                
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SearchData::class,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

}