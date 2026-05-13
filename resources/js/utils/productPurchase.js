const getSkuAvailableStock = (sku) => {
  if (Number.isFinite(Number(sku?.available_stock))) {
    return Number(sku.available_stock);
  }

  if (Array.isArray(sku?.stocks) && sku.stocks.length > 0) {
    return sku.stocks.reduce((total, stock) => {
      const onHand = Number(stock?.on_hand || 0);
      const reserved = Number(stock?.reserved || 0);
      return total + (onHand - reserved);
    }, 0);
  }

  return Number(sku?.stock_quantity || 0);
};

const getActiveSkus = (product) => {
  const skus = Array.isArray(product?.skus) ? product.skus : [];
  return skus.filter((sku) => sku && sku.active !== false);
};

export const resolveProductPurchase = (product) => {
  const skus = getActiveSkus(product);
  const hasOptions = Boolean(product?.has_options || product?.requires_option_selection);
  const isBasket = product?.product_type === 'basket';

  if (!skus.length) {
    return {
      canAddDirectly: false,
      requiresSelection: false,
      outOfStock: true,
      selectedSkuId: null,
      reason: 'unavailable',
    };
  }

  if (isBasket) {
    const basketAvailableStock = Number(product?.basket_available_stock || 0);
    const selectedSkuId = product?.default_sku_id || skus[0]?.id || null;

    return {
      canAddDirectly: basketAvailableStock > 0 && Boolean(selectedSkuId),
      requiresSelection: false,
      outOfStock: basketAvailableStock <= 0 || !selectedSkuId,
      selectedSkuId,
      reason: basketAvailableStock > 0 && selectedSkuId ? null : 'out_of_stock',
    };
  }

  const inStockSkus = skus.filter((sku) => getSkuAvailableStock(sku) > 0);

  if (!inStockSkus.length) {
    return {
      canAddDirectly: false,
      requiresSelection: false,
      outOfStock: true,
      selectedSkuId: null,
      reason: 'out_of_stock',
    };
  }

  if (hasOptions || skus.length > 1) {
    return {
      canAddDirectly: false,
      requiresSelection: true,
      outOfStock: false,
      selectedSkuId: null,
      reason: 'selection_required',
    };
  }

  return {
    canAddDirectly: true,
    requiresSelection: false,
    outOfStock: false,
    selectedSkuId: inStockSkus[0].id,
    reason: null,
  };
};
