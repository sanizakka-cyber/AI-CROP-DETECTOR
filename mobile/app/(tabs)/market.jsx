import React, { useEffect, useState, useCallback } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  TextInput, RefreshControl, FlatList, ActivityIndicator, Image,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { marketplaceAPI, cartAPI } from '../../lib/api';
import { Colors, Spacing, Radius, Typography, Shadows } from '../../constants/Theme';

// ── Category config ──────────────────────────────────────────────────────────
const CATEGORIES = [
  { id: '',                    label: 'All',                icon: '🏪' },
  { id: 'Livestock Feed',      label: 'Livestock Feed',     icon: '🌾' },
  { id: 'Veterinary Medicines',label: 'Vet Medicines',      icon: '💊' },
  { id: 'Vaccines',            label: 'Vaccines',           icon: '💉' },
  { id: 'Animal Health',       label: 'Animal Health',      icon: '🐄' },
  { id: 'Crop Protection',     label: 'Crop Protection',    icon: '🌿' },
  { id: 'Fertilizers',         label: 'Fertilizers',        icon: '🪣' },
  { id: 'Seeds',               label: 'Seeds',              icon: '🌱' },
  { id: 'Veterinary Equipment',label: 'Vet Equipment',      icon: '🔬' },
  { id: 'Farming Equipment',   label: 'Farm Equipment',     icon: '🚜' },
];

const SORT_OPTIONS = [
  { id: 'newest',     label: 'Newest' },
  { id: 'price_asc',  label: 'Price ↑' },
  { id: 'price_desc', label: 'Price ↓' },
  { id: 'rating',     label: 'Top Rated' },
];

const STOCK_COLORS = {
  in_stock:    { bg: '#D1FAE5', text: '#065F46', label: 'In Stock' },
  low_stock:   { bg: '#FEF3C7', text: '#92400E', label: 'Low Stock' },
  out_of_stock:{ bg: '#FEE2E2', text: '#991B1B', label: 'Out of Stock' },
};

// ── Product Card ─────────────────────────────────────────────────────────────
function ProductCard({ product, onPress, onAddToCart, cartLoading }) {
  const stock = STOCK_COLORS[product.stock_status] || STOCK_COLORS.in_stock;
  const isOOS  = product.stock_status === 'out_of_stock';
  const icon   = CATEGORIES.find(c => c.id === product.category)?.icon || '📦';

  return (
    <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.85}>
      {/* Product image or icon placeholder */}
      <View style={styles.cardImage}>
        {product.image_url
          ? <Image source={{ uri: product.image_url }} style={styles.cardImageImg} />
          : <Text style={styles.cardIcon}>{icon}</Text>
        }
        <View style={[styles.stockBadge, { backgroundColor: stock.bg }]}>
          <Text style={[styles.stockText, { color: stock.text }]}>{stock.label}</Text>
        </View>
      </View>

      <View style={styles.cardBody}>
        <Text style={styles.cardCat}>{product.category}</Text>
        <Text style={styles.cardName} numberOfLines={2}>{product.name}</Text>
        <Text style={styles.cardBrand}>{product.brand} · {product.unit}</Text>

        {/* Rating */}
        {product.rating_count > 0 && (
          <View style={styles.ratingRow}>
            <Text style={styles.ratingStar}>★</Text>
            <Text style={styles.ratingVal}>{Number(product.rating).toFixed(1)}</Text>
            <Text style={styles.ratingCount}>({product.rating_count})</Text>
          </View>
        )}

        <View style={styles.cardFooter}>
          <Text style={styles.cardPrice}>₦{Number(product.selling_price).toLocaleString()}</Text>
          <TouchableOpacity
            style={[styles.cartBtn, isOOS && styles.cartBtnDisabled]}
            onPress={() => !isOOS && onAddToCart(product)}
            disabled={isOOS || cartLoading}
          >
            {cartLoading
              ? <ActivityIndicator size="small" color={Colors.white} />
              : <Text style={styles.cartBtnText}>{isOOS ? 'Out' : '🛒 Add'}</Text>
            }
          </TouchableOpacity>
        </View>
      </View>
    </TouchableOpacity>
  );
}

// ── Main Screen ───────────────────────────────────────────────────────────────
export default function MarketScreen() {
  const router = useRouter();
  const { t } = useTranslation();

  const [products, setProducts]     = useState([]);
  const [loading, setLoading]       = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch]         = useState('');
  const [category, setCategory]     = useState('');
  const [sort, setSort]             = useState('newest');
  const [cartCount, setCartCount]   = useState(0);
  const [cartLoading, setCartLoading] = useState(null); // product id being added
  const [toast, setToast]           = useState(null);
  const [page, setPage]             = useState(1);
  const [hasMore, setHasMore]       = useState(true);
  const [loadError, setLoadError]   = useState(null);

  const showToast = (msg, ok = true) => {
    setToast({ msg, ok });
    setTimeout(() => setToast(null), 2500);
  };

  const loadProducts = useCallback(async (reset = true) => {
    try {
      if (reset) setLoading(true);
      setLoadError(null);
      const pg = reset ? 1 : page + 1;
      const res = await marketplaceAPI.products({ category, search, sort, page: pg, per_page: 20 });
      const list = res.data || res.products || [];
      // Real catalog data only — no fake/demo products substituted when the
      // list is empty. An empty catalog is shown honestly (see
      // ListEmptyComponent below), not disguised with placeholder products.
      if (reset) {
        setProducts(list);
      } else {
        setProducts(prev => [...prev, ...list]);
      }
      setHasMore(list.length === 20);
      if (!reset) setPage(pg);
    } catch (e) {
      if (reset) {
        setProducts([]);
        setLoadError(e?.message || 'Could not load products. Please check your connection and try again.');
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [category, search, sort, page]);

  useEffect(() => { loadProducts(true); }, [category, sort]);

  // Debounced search
  useEffect(() => {
    const timer = setTimeout(() => loadProducts(true), 500);
    return () => clearTimeout(timer);
  }, [search]);

  // Load cart count
  useEffect(() => {
    cartAPI.count().then(n => setCartCount(n)).catch(() => {});
  }, []);

  const onRefresh = () => { setRefreshing(true); loadProducts(true); };

  const addToCart = async (product) => {
    setCartLoading(product.id || product._id);
    try {
      await cartAPI.add(product.id || product._id, 1);
      setCartCount(c => c + 1);
      showToast(`${product.name} added to cart`);
    } catch (e) {
      showToast(e?.message || 'Could not add to cart', false);
    } finally {
      setCartLoading(null);
    }
  };

  const openProduct = (product) => {
    router.push({ pathname: '/market/product', params: { id: product.id || product._id } });
  };

  return (
    <View style={styles.root}>
      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerTop}>
          <View>
            <Text style={styles.headerTitle}>Agro Marketplace</Text>
            <Text style={styles.headerSub}>Agricultural inputs & veterinary supplies</Text>
          </View>
          <TouchableOpacity style={styles.cartIconBtn} onPress={() => router.push('/market/cart')}>
            <Text style={styles.cartIconText}>🛒</Text>
            {cartCount > 0 && (
              <View style={styles.cartBadge}>
                <Text style={styles.cartBadgeText}>{cartCount > 99 ? '99+' : cartCount}</Text>
              </View>
            )}
          </TouchableOpacity>
        </View>

        {/* Search */}
        <View style={styles.searchWrap}>
          <Text style={styles.searchIcon}>🔍</Text>
          <TextInput
            style={styles.searchInput}
            value={search}
            onChangeText={setSearch}
            placeholder="Search products, brands, diseases..."
            placeholderTextColor="rgba(255,255,255,0.6)"
          />
          {search.length > 0 && (
            <TouchableOpacity onPress={() => setSearch('')}>
              <Text style={{ color: Colors.white, fontSize: 16 }}>✕</Text>
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* Category tabs */}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.catScroll} contentContainerStyle={{ paddingHorizontal: Spacing.md }}>
        {CATEGORIES.map(c => (
          <TouchableOpacity
            key={c.id}
            style={[styles.catChip, category === c.id && styles.catChipActive]}
            onPress={() => setCategory(c.id)}
          >
            <Text style={styles.catIcon}>{c.icon}</Text>
            <Text style={[styles.catLabel, category === c.id && styles.catLabelActive]}>{c.label}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {/* Sort bar */}
      <View style={styles.sortBar}>
        <Text style={styles.sortLabel}>Sort by:</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
          {SORT_OPTIONS.map(s => (
            <TouchableOpacity
              key={s.id}
              style={[styles.sortChip, sort === s.id && styles.sortChipActive]}
              onPress={() => setSort(s.id)}
            >
              <Text style={[styles.sortChipText, sort === s.id && { color: Colors.white }]}>{s.label}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      {/* Product grid */}
      {loading ? (
        <View style={styles.loader}>
          <ActivityIndicator size="large" color={Colors.primary} />
          <Text style={styles.loaderText}>Loading products...</Text>
        </View>
      ) : (
        <FlatList
          data={products}
          keyExtractor={item => String(item.id || item._id)}
          numColumns={2}
          columnWrapperStyle={styles.row}
          contentContainerStyle={styles.grid}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={Colors.primary} />}
          renderItem={({ item }) => (
            <ProductCard
              product={item}
              onPress={() => openProduct(item)}
              onAddToCart={addToCart}
              cartLoading={cartLoading === (item.id || item._id)}
            />
          )}
          ListEmptyComponent={
            loadError ? (
              <View style={styles.empty}>
                <Text style={styles.emptyIcon}>⚠️</Text>
                <Text style={styles.emptyText}>Unable to load products</Text>
                <Text style={styles.emptySub}>{loadError}</Text>
                <TouchableOpacity style={styles.retryBtn} onPress={() => loadProducts(true)}>
                  <Text style={styles.retryBtnText}>Try Again</Text>
                </TouchableOpacity>
              </View>
            ) : (search || category) ? (
              <View style={styles.empty}>
                <Text style={styles.emptyIcon}>🔍</Text>
                <Text style={styles.emptyText}>No products found</Text>
                <Text style={styles.emptySub}>Try a different category or search term</Text>
              </View>
            ) : (
              <View style={styles.empty}>
                <Text style={styles.emptyIcon}>🏪</Text>
                <Text style={styles.emptyText}>No products available yet</Text>
                <Text style={styles.emptySub}>Sellers haven't listed products in this marketplace yet — check back soon</Text>
              </View>
            )
          }
          onEndReached={() => hasMore && loadProducts(false)}
          onEndReachedThreshold={0.3}
        />
      )}

      {/* Toast */}
      {toast && (
        <View style={[styles.toast, { backgroundColor: toast.ok ? Colors.success : Colors.danger }]}>
          <Text style={styles.toastText}>{toast.msg}</Text>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#F8FAFC' },

  header: { backgroundColor: Colors.primary, paddingTop: 52, paddingBottom: 12, paddingHorizontal: Spacing.md },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 },
  headerTitle: { fontSize: 20, fontWeight: '800', color: Colors.white },
  headerSub: { fontSize: 12, color: 'rgba(255,255,255,0.75)', marginTop: 2 },
  cartIconBtn: { position: 'relative', padding: 4 },
  cartIconText: { fontSize: 26 },
  cartBadge: { position: 'absolute', top: -4, right: -4, backgroundColor: Colors.danger, borderRadius: 10, minWidth: 20, height: 20, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 4 },
  cartBadgeText: { color: Colors.white, fontSize: 10, fontWeight: '700' },

  searchWrap: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: Radius.full, paddingHorizontal: 14, paddingVertical: 8, gap: 8 },
  searchIcon: { fontSize: 16 },
  searchInput: { flex: 1, color: Colors.white, fontSize: 14 },

  catScroll: { backgroundColor: Colors.white, borderBottomWidth: 1, borderBottomColor: '#E2E8F0', paddingVertical: 10 },
  catChip: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 6, borderRadius: Radius.full, borderWidth: 1.5, borderColor: '#E2E8F0', marginRight: 8, backgroundColor: Colors.white },
  catChipActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  catIcon: { fontSize: 14, marginRight: 4 },
  catLabel: { fontSize: 12, color: '#64748B', fontWeight: '500' },
  catLabelActive: { color: Colors.white, fontWeight: '700' },

  sortBar: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: Spacing.md, paddingVertical: 8, backgroundColor: Colors.white, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  sortLabel: { fontSize: 12, color: '#94A3B8', marginRight: 8, fontWeight: '600' },
  sortChip: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: Radius.full, backgroundColor: '#F1F5F9', marginRight: 6 },
  sortChipActive: { backgroundColor: Colors.primary },
  sortChipText: { fontSize: 12, color: '#475569', fontWeight: '500' },

  grid: { padding: Spacing.md, paddingBottom: 100 },
  row: { justifyContent: 'space-between', marginBottom: Spacing.sm },

  card: { width: '48.5%', backgroundColor: Colors.white, borderRadius: 14, overflow: 'hidden', ...Shadows.card },
  cardImage: { height: 110, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center', position: 'relative' },
  cardImageImg: { width: '100%', height: '100%', resizeMode: 'cover' },
  cardIcon: { fontSize: 42 },
  stockBadge: { position: 'absolute', top: 6, right: 6, paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  stockText: { fontSize: 9, fontWeight: '700' },

  cardBody: { padding: 10 },
  cardCat: { fontSize: 9, color: Colors.primary, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.4, marginBottom: 2 },
  cardName: { fontSize: 13, fontWeight: '700', color: '#1E293B', lineHeight: 17 },
  cardBrand: { fontSize: 10, color: '#94A3B8', marginTop: 3 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', marginTop: 4 },
  ratingStar: { color: '#F59E0B', fontSize: 11 },
  ratingVal: { fontSize: 11, fontWeight: '700', color: '#64748B', marginLeft: 2 },
  ratingCount: { fontSize: 10, color: '#94A3B8', marginLeft: 2 },
  cardFooter: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 8 },
  cardPrice: { fontSize: 14, fontWeight: '800', color: Colors.primary },
  cartBtn: { backgroundColor: Colors.primary, paddingHorizontal: 10, paddingVertical: 5, borderRadius: 8 },
  cartBtnDisabled: { backgroundColor: '#CBD5E1' },
  cartBtnText: { color: Colors.white, fontSize: 11, fontWeight: '700' },

  loader: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingTop: 80 },
  loaderText: { marginTop: 12, color: '#64748B', fontSize: 14 },

  empty: { alignItems: 'center', paddingTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: 12 },
  emptyText: { fontSize: 16, fontWeight: '700', color: '#334155' },
  emptySub: { fontSize: 13, color: '#94A3B8', marginTop: 4, textAlign: 'center', paddingHorizontal: 32 },
  retryBtn: { marginTop: 16, backgroundColor: Colors.primary, borderRadius: Radius.md, paddingHorizontal: 20, paddingVertical: 10 },
  retryBtnText: { color: Colors.white, fontWeight: '700', fontSize: 13 },

  toast: { position: 'absolute', bottom: 90, left: Spacing.md, right: Spacing.md, borderRadius: 10, padding: 12, alignItems: 'center' },
  toastText: { color: Colors.white, fontWeight: '600', fontSize: 13 },
});
