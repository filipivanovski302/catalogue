<?php


namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;
/**
 * Create Migration product class
 */
class CreateGripTrainerProduct implements DataPatchInterface
{
    protected ProductFactory $prodcutFactory;
    protected ProductInterfaceFactory $productInterfaceFactory;
    protected ProductRepositoryInterface $productRepository;
    protected State $appState;
    protected StoreManagerInterface $storeManager;
    protected SourceItemInterfaceFactory $sourceItemFactory;
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;
    protected EavSetup $eavSetup;
    protected CategoryLinkManagementInterface $categoryLink;
    protected array $sourceItems = [];

    
    public function __construct(
        ProductFactory $prodcutFactory,
        ProductInterfaceFactory $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        State $appState,
        StoreManagerInterface $storeManager,
        EavSetup $eavSetup,
				SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
				CategoryLinkManagementInterface $categoryLink
    ) {
        $this->prodcutFactory = $prodcutFactory;
        $this->appState = $appState;
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
				$this->eavSetup = $eavSetup;
        $this->storeManager = $storeManager;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
				$this->categoryLink = $categoryLink;
    }

  
    public function apply(): void
    {
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

 
    public function execute(): void
    {
        $product = $this->prodcutFactory->create();
        try{
            if ($this->productRepository->get('grip-trainer')) {
                return;
            }
        }catch(\Magento\Framework\Exception\NoSuchEntityException $e){}
        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');
        $websiteIDs = [$this->storeManager->getStore()->getWebsiteId()];
			$product->setTypeId(Type::TYPE_SIMPLE)
                ->setWebsiteIds($websiteIDs)
                ->setAttributeSetId($attributeSetId)
                ->setName('Grip Trainer')
                ->setUrlKey('griptrainer')
                ->setSku('grip-trainer')
                ->setPrice(9.99)
                ->setVisibility(Visibility::VISIBILITY_BOTH)
                ->setStatus(Status::STATUS_ENABLED)
                ->setStockData(['use_config_manage_stock' => 1, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
        $product = $this->productRepository->save($product);

        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setQuantity(10);
        $sourceItem->setSku($product->getSku());
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItems[] = $sourceItem;

        $this->sourceItemsSaveInterface->execute($this->sourceItems);

		$this->categoryLink->assignProductToCategories($product->getSku(), [2]);
    }


    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}