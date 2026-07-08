<?php

namespace App\Repository;

use App\Entity\Cabinet;
use App\Entity\Patient;
use App\Data\SearchData;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Patient|null find($id, $lockMode = null, $lockVersion = null)
 * @method Patient|null findOneBy(array $criteria, array $orderBy = null)
 * @method Patient[]    findAll()
 * @method Patient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PatientRepository extends ServiceEntityRepository
{
    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Patient::class);
        $this->paginator = $paginator;
    }

    /**
     * @return PaginationInterface
     */
    public function getAll(SearchData $data)
    {
        $entityManager= $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT p
            FROM App\Entity\Patient p
            ORDER BY p.id"
        );
        //return  $query->execute();
        return $this->paginator->paginate(
            $query,
            $data->page,
            10
    );
    }

    public function getByCabinet(Cabinet $cabinet)
    {
        $query = $this->createQueryBuilder("p")
                ->innerJoin("p.cabinet","c", "WITH","c.id= :id")
                ->setParameter("id",$cabinet);
        
        return $query->getQuery()->GetResult();
        /*$entityManager= $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT p
             FROM App\Entity\Patient p
             JOIN App\Entity\Cabinet c
             WHERE c.id = :id
             ORDER BY p.id"
        )->setParameter("id",1);
        return $query->execute();*/
    }

    public function getBySearch(SearchData $data)
    {
        // if(count($data->cabinets) > 100)
        // {
        //     $rsm = new ResultSetMapping();
        //     $rsm->addEntityResult('App\Entity\Patient','p');
        //     $rsm->addFieldResult('p', 'id', 'id');
        //     $rsm->addFieldResult('p', 'nom', 'nom');
        //     $rsm->addFieldResult('p', 'prenom', 'prenom');
        //     $rsm->addFieldResult('p', 'num_fixe', 'numFixe');
        //     $rsm->addFieldResult('p', 'num_portable', 'numPortable');
        //     $rsm->addFieldResult('p', 'adresse', 'adresse');
        //     $rsm->addFieldResult('p', 'code_postal', 'codePostal');
        //     $rsm->addFieldResult('p', 'ville', 'ville');

        //     $q ="SELECT p.id, p.nom, p.prenom, p.num_fixe, p.num_portable, p.adresse, p.code_postal, p.ville
        //         FROM patient p
        //         INNER JOIN patient_cabinet pc ON p.id = pc.patient_id
        //         WHERE p.nom LIKE :q
        //         OR p.prenom LIKE :q
        //         OR p.num_fixe LIKE :q
        //         OR p.num_portable LIKE :q
        //         OR p.code_postal LIKE :q
        //         OR p.adresse LIKE :q
        //         OR p.ville LIKE :q
        //         GROUP BY p.id";

        //     $counter = 1;

        //     foreach($data->cabinets as $cabinet)
        //     {
        //         $id = $cabinet->getId();
        //         if($counter == 1)
        //             $q= $q." HAVING GROUP_CONCAT(pc.cabinet_id) LIKE :id$id";
        //         else
        //             $q= $q." AND GROUP_CONCAT(pc.cabinet_id) LIKE :id$id";
        //         $counter++;
        //     }

        //     $em = $this->getEntityManager();
        //     $query = $em->createNativeQuery($q,$rsm);
        //     foreach($data->cabinets as $cabinet)
        //     {
        //         $id = $cabinet->getId();
        //         $query->setParameter("id$id", "%{$id}%");
        //     }

        //     $query->setParameter("q", "%{$data->q}%");
        //     //return $query->getResult();
        //     return $this->paginator->paginate(
        //         $query->getResult(),
        //         $data->page,
        //         50
        //  );
        //}
        //else
       //{
            if($data->cabinets)
                $query = $this->createQueryBuilder("p")
                ->select("c","p")
                ->join("p.cabinet","c");
            else
                $query = $this->createQueryBuilder("p")
                ->select("p");

            if(!empty($data->q))
            {
            $query = $query
                    ->Where("p.nom LIKE :q")
                    ->orWhere("p.prenom LIKE :q")
                    ->orWhere("p.prenom LIKE :q")
                    ->orWhere("p.numFixe LIKE :q")
                    ->orWhere("p.numPortable LIKE :q")
                    ->orWhere("p.codePostal LIKE :q")
                    ->orWhere("p.adresse LIKE :q")
                    ->orWhere("p.ville LIKE :q")
                    ->setParameter("q", "%{$data->q}%");
            };
            if(!empty($data->cabinets))
            {           
                $id = $data->cabinets[0]->getId();
                $q = "";
                $nbcabinet = count($data->cabinets);
                $i=0;
                foreach($data->cabinets as $cabinet)
                {
                    $id = $cabinet->getId();
                    if(++$i === $nbcabinet)
                        $q= $q."c.id = :id$id";
                    else
                        $q= $q."c.id = :id$id or "; 
                    $query = $query->setParameter("id$id", $id);
                }
                $query = $query
                    ->andWhere($q);
                
            }

             $query = $query->getQuery();
             
             //return $query->getQuery()->GetResult();
             return $this->paginator->paginate(
                    $query,
                    $data->page,
                    10
             );
      // }
        
    }

    public function getOneById($id)
    {
        $entityManager= $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT p 
             FROM App\Entity\Patient p
             WHERE p.id = :id"
        );
        $query->setParameter('id',$id);
        
        return $query->GetResult();
    }




    // /**
    //  * @return Patient[] Returns an array of Patient objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Patient
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
