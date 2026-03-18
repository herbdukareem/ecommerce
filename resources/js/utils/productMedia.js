const DEFAULT_PLACEHOLDER = '/images/placeholders/product-placeholder.svg';

const normalizeUrl = (value) => {
  if (!value || typeof value !== 'string') return null;
  const trimmed = value.trim();
  if (!trimmed) return null;
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('/')) {
    return trimmed;
  }
  return `/${trimmed.replace(/^\/+/, '')}`;
};

const sortByOrder = (a, b) => Number(a?.order || 0) - Number(b?.order || 0);

export const normalizeProductMedia = (product) => {
  if (!product || typeof product !== 'object') return { primaryImage: null, gallery: [] };

  const relationImages = Array.isArray(product.images) ? [...product.images].sort(sortByOrder) : [];
  const relationGallery = relationImages
    .map((image) => normalizeUrl(image.image_url || image.image_path))
    .filter(Boolean);

  const primaryFromRelation = relationImages.find((image) => image.is_primary);
  const primaryImage = normalizeUrl(
    primaryFromRelation?.image_url ||
    primaryFromRelation?.image_path ||
    relationGallery[0] ||
    product.primary_image_url ||
    product.image
  );

  const uniqueGallery = Array.from(new Set([
    primaryImage,
    ...relationGallery,
  ].filter(Boolean)));

  return {
    primaryImage,
    gallery: uniqueGallery,
    placeholder: DEFAULT_PLACEHOLDER,
  };
};
