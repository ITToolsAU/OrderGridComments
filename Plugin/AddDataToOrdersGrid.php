<?php
namespace Ittools\OrderGridComments\Plugin;

/**
 * Class AddDataToOrdersGrid
 */
class AddDataToOrdersGrid
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * AddDataToOrdersGrid constructor.
     *
     * @param \Psr\Log\LoggerInterface $customLogger
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $customLogger,
        array $data = []
    ) {
        $this->logger   = $customLogger;
    }

    /**
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\Collection $collection
     * @param $requestName
     * @return mixed
     */
    public function afterGetReport($subject, $collection, $requestName)
    {
        if ($requestName !== 'sales_order_grid_data_source') {
            return $collection;
        }

        if ($collection->getMainTable() === $collection->getResource()->getTable('sales_order_grid')) {
            try {
                $orderCommentsTableName = $collection->getResource()->getTable('sales_order_status_history');
                $subquery = new \Zend_Db_Expr("(select parent_id,
    group_concat(comment order by parent_id asc separator ' <br/> ') as comment
  from
    {$orderCommentsTableName}
  group by
    parent_id)");
                $collection->getSelect()->joinLeft(
                    ['sosh' => $subquery ],
                    'sosh.parent_id = main_table.entity_id',
                    ['sosh.comment']
                );
            } catch (\Zend_Db_Select_Exception $selectException) {
                // Do nothing in that case
                $this->logger->log(100, $selectException);
            }
        }
        
        return $collection;
    }
}
