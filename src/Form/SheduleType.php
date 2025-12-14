<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Shedule;
use App\Entity\Teacher;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('room')
            ->add('time')
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'id',
            ])
            ->add('teacher', EntityType::class, [
                'class' => Teacher::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Shedule::class,
        ]);
    }
}
