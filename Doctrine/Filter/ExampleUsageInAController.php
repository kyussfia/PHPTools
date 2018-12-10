<?php

namespace App\Controller;

use App\Entity\ExpenseItem;
use App\Entity\Invoice;
use App\Entity\VirtualFilters\InvoiceCanBePaidFilter;
use App\Entity\VirtualFilters\InvoiceHasItemsFilter;
use App\Entity\VirtualFilters\InvoiceIsSettledFilter;
use App\Exception\InvoiceBusinessLogicException;
use App\Mailer\MailFactory;
use Slametrix\Doctrine\ORM\Filter\Filter;
use Slametrix\Doctrine\ORM\Filter\FilterCollection;
use Slametrix\Doctrine\ORM\Sorter\SorterCollection;
use FOS\RestBundle\Controller\Annotations as FOS;
use FOS\RestBundle\Request\ParamFetcherInterface;
use App\Response\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/app/invoices")
 */
class InvoiceController extends \App\Controller\BaseController
{
    /**
     * @Route("/getMyInvoices",
     *     defaults={"_format" : "json"}
     * )
     * @Method({"GET"})
     * @FOS\View(serializerEnableMaxDepthChecks=true,serializerGroups={"invoice_list_my", "invoice_list"})
     *
     * @FOS\QueryParam(name="start", requirements="\d+", nullable=true, description="Offset from which to start listing notes.")
     * @FOS\QueryParam(name="limit", requirements="\d+", nullable=true, description="How many notes to return.")
     * @FOS\QueryParam(name="sort", nullable=false, strict=true, requirements="\[{.*]", default="[]", description="Sort field. Must be a json array ie. [{},{}]")
     * @FOS\QueryParam(name="filter", nullable=true, strict=true, requirements="\[{.*]", default="[]", description="Filter by fields. Must be a json array ie. [{},{}]")
     *
     * @param \FOS\RestBundle\Request\ParamFetcherInterface $paramFetcher
     * @param \Slametrix\Doctrine\ORM\Filter\FilterCollection $filter
     * @param \Slametrix\Doctrine\ORM\Sorter\SorterCollection $sort
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function getMyInvoicesAction(
        ParamFetcherInterface $paramFetcher,
        FilterCollection $filter = null,
        SorterCollection $sort = null
    ) {
        try {
            $hasItemsSorter = \Slametrix\Doctrine\ORM\Sorter\ArraySorter::initFromSorterCollection('hasItems', $sort);
            $isSettledSorter = \Slametrix\Doctrine\ORM\Sorter\ArraySorter::initFromSorterCollection('isSettled', $sort);
            $canBePaidSorter = \Slametrix\Doctrine\ORM\Sorter\ArraySorter::initFromSorterCollection('canBePaid', $sort);
            $canBePaidFilter = InvoiceCanBePaidFilter::initFromFilterCollection('canBePaid', $filter);
            $hasItemsFilter = InvoiceHasItemsFilter::initFromFilterCollection('hasItems', $filter);
            $isSettledFilter = InvoiceIsSettledFilter::initFromFilterCollection('isSettled', $filter);

            $repository = $this->getDoctrine()->getManager()->getRepository('App:Invoice');

            $offset = $paramFetcher->get('start');
            $limit = $paramFetcher->get('limit');

            $resultWithTotalCount = $repository->findVisibleByUser(
                $this->getUser(),
                $filter,
                $sort
            );

            $total = $resultWithTotalCount['totalCount'];
            $invoices = $resultWithTotalCount['result'];

            $hasItemsSorter->sort($invoices, 'hasItems');
            $isSettledSorter->sort($invoices, 'isSettled');
            $canBePaidSorter->sort($invoices, 'canBePaid');
            $hasItemsFilter->filter($invoices, 'hasItems', $total);
            $isSettledFilter->filter($invoices, 'isSettled', $total);
            $canBePaidFilter->filterAndPage($invoices, 'canBePaid', $total, $offset, $limit);

            return array('total' => $total, 'data' => $invoices, 'success' => true);
        }
        catch (\App\Exception\TranslatableException $e) {
            return Response::create($e);
        }
    }
}