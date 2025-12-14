<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Exam;
use App\Entity\Student;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('examDate')
            ->add('grades')
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'id',
            ])
            ->add('students', EntityType::class, [
                'class' => Student::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exam::class,
        ]);
    }
}
