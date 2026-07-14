import React, { useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  ActivityIndicator, Image, Alert,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { marketplaceAPI, cartAPI } from '../../lib/api';
import { Colors, Spacing, Radius, Shadows } from '../../constants/Theme';

const STOCK_STYLES = {
  in_stock:    { bg: '#D1FAE5', color: '#065F46', label: '✓ In Stock' },
  low_stock:   { bg: '#FEF3C7', color: '#92400E', label: '⚠ Low Stock' },
  out_of_stock:{ bg: '#FEE2E2', color: '#991B1B', label: '✗ Out of Stock' },
};

export default function ProductScreen() {
  const { id } = useLocalSearchParams();
  const router  = useRouter();
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [qty, setQty]         = useState(1);
  const [adding, setAdding]   = useState(false);
  const [tab, setTab]         = useState('details'); // details | instructions | reviews

  useEffect(() => {
    marketplaceAPI.product(id)
      .then(res => setProduct(res.data))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, [id]);

  const addToCart = async () => {
    setAdding(true);
    try {
      await cartAPI.add(product.id, qty);
      Alert.alert('Added to Cart', `${product.name} (×${qty}) added to your cart.`, [
        { text: 'View Cart', onPress: () => router.push('/market/cart') },
        { text: 'Continue Shopping', style: 'cancel' },
      ]);
    } catch (e) {
      Alert.alert('Error', e.message || 'Could not add to cart');
    } finally {
      setAdding(false);
    }
  };

  if (loading) return <View style={styles.loader}><ActivityIndicator size="large" color={Colors.primary} /></View>;
  if (!product) return (
    <View style={styles.loader}>
      <Text style={{ color: '#64748B', fontSize: 16 }}>Product not found</Text>
      <TouchableOpacity onPress={() => router.back()} style={{ marginTop: 16 }}>
        <Text style={{ color: Colors.primary, fontWeight: '600' }}>← Go back</Text>
      </TouchableOpacity>
    </View>
  );

  const stock = STOCK_STYLES[product.stock_status] || STOCK_STYLES.in_stock;
  const isOOS = product.stock_status === 'out_of_stock';

  return (
    <View style={{ flex: 1, backgroundColor: '#F8FAFC' }}>
      <ScrollView contentContainerStyle={{ paddingBottom: 140 }}>
        {/* Image / header */}
        <View style={styles.imageWrap}>
          {product.image_url
            ? <Image source={{ uri: product.image_url }} style={styles.image} />
            : <Text style={styles.imagePlaceholder}>📦</Text>
          }
          <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
            <Text style={{ color: Colors.white, fontSize: 18 }}>←</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.content}>
          {/* Title row */}
          <View style={[styles.stockPill, { backgroundColor: stock.bg }]}>
            <Text style={[styles.stockText, { color: stock.color }]}>{stock.label}</Text>
          </View>
          <Text style={styles.category}>{product.category}</Text>
          <Text style={styles.name}>{product.name}</Text>
          {product.brand && <Text style={styles.brand}>{product.brand} · {product.manufacturer}</Text>}

          {/* Rating */}
          {product.rating_count > 0 && (
            <View style={styles.ratingRow}>
              {[1,2,3,4,5].map(s => (
                <Text key={s} style={{ fontSize: 16, color: s <= Math.round(product.rating) ? '#F59E0B' : '#CBD5E1' }}>★</Text>
              ))}
              <Text style={styles.ratingText}> {Number(product.rating).toFixed(1)} ({product.rating_count} reviews)</Text>
            </View>
          )}

          {/* Price + qty */}
          <View style={styles.priceRow}>
            <Text style={styles.price}>₦{Number(product.selling_price).toLocaleString()}</Text>
            <Text style={styles.unit}>per {product.unit}</Text>
          </View>

          {/* Quantity selector */}
          {!isOOS && (
            <View style={styles.qtyRow}>
              <Text style={styles.qtyLabel}>Quantity:</Text>
              <TouchableOpacity style={styles.qtyBtn} onPress={() => setQty(q => Math.max(1, q - 1))}>
                <Text style={styles.qtyBtnText}>−</Text>
              </TouchableOpacity>
              <Text style={styles.qtyVal}>{qty}</Text>
              <TouchableOpacity style={styles.qtyBtn} onPress={() => setQty(q => Math.min(product.quantity_in_stock, q + 1))}>
                <Text style={styles.qtyBtnText}>+</Text>
              </TouchableOpacity>
              <Text style={styles.stockCount}>{product.quantity_in_stock} available</Text>
            </View>
          )}

          {/* Tab navigation */}
          <View style={styles.tabs}>
            {['details','instructions','reviews'].map(t => (
              <TouchableOpacity key={t} style={[styles.tab, tab === t && styles.tabActive]} onPress={() => setTab(t)}>
                <Text style={[styles.tabText, tab === t && styles.tabTextActive]}>
                  {t === 'details' ? 'Details' : t === 'instructions' ? 'Usage' : 'Reviews'}
                </Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* Tab content */}
          {tab === 'details' && (
            <View style={styles.tabContent}>
              {product.description && <Text style={styles.desc}>{product.description}</Text>}
              {product.storage_requirements && (
                <View style={styles.infoBox}>
                  <Text style={styles.infoBoxTitle}>🌡 Storage</Text>
                  <Text style={styles.infoBoxText}>{product.storage_requirements}</Text>
                </View>
              )}
              {product.expiry_date && (
                <View style={styles.infoBox}>
                  <Text style={styles.infoBoxTitle}>📅 Expiry Date</Text>
                  <Text style={styles.infoBoxText}>{product.expiry_date}</Text>
                </View>
              )}
              <View style={styles.infoBox}>
                <Text style={styles.infoBoxTitle}>📦 Unit</Text>
                <Text style={styles.infoBoxText}>{product.unit}</Text>
              </View>
              {product.sku && (
                <View style={styles.infoBox}>
                  <Text style={styles.infoBoxTitle}>🏷 SKU</Text>
                  <Text style={styles.infoBoxText}>{product.sku}</Text>
                </View>
              )}
            </View>
          )}

          {tab === 'instructions' && (
            <View style={styles.tabContent}>
              {product.usage_instructions
                ? <><Text style={styles.infoBoxTitle}>📋 Usage Instructions</Text><Text style={styles.desc}>{product.usage_instructions}</Text></>
                : null}
              {product.dosage_instructions
                ? (<View style={{ marginTop: 16 }}><Text style={styles.infoBoxTitle}>💊 Dosage Instructions</Text><Text style={styles.desc}>{product.dosage_instructions}</Text></View>)
                : null}
              {!product.usage_instructions && !product.dosage_instructions && (
                <Text style={styles.desc}>No usage instructions available for this product.</Text>
              )}
            </View>
          )}

          {tab === 'reviews' && (
            <View style={styles.tabContent}>
              {product.reviews?.length > 0
                ? product.reviews.map(r => (
                    <View key={r.id} style={styles.reviewCard}>
                      <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                        <Text style={styles.reviewName}>{r.user?.name || 'User'}</Text>
                        <Text style={{ color: '#F59E0B' }}>{'★'.repeat(r.rating)}</Text>
                      </View>
                      {r.review && <Text style={styles.reviewText}>{r.review}</Text>}
                    </View>
                  ))
                : <Text style={styles.desc}>No reviews yet. Be the first to review!</Text>}
            </View>
          )}
        </View>
      </ScrollView>

      {/* Sticky bottom bar */}
      <View style={styles.bottomBar}>
        <View>
          <Text style={styles.bottomPrice}>₦{(Number(product.selling_price) * qty).toLocaleString()}</Text>
          <Text style={styles.bottomQty}>for {qty} × {product.unit}</Text>
        </View>
        <TouchableOpacity
          style={[styles.addBtn, isOOS && styles.addBtnDisabled]}
          onPress={addToCart}
          disabled={isOOS || adding}
        >
          {adding
            ? <ActivityIndicator color={Colors.white} />
            : <Text style={styles.addBtnText}>{isOOS ? 'Out of Stock' : '🛒 Add to Cart'}</Text>
          }
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  loader: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  imageWrap: { height: 220, backgroundColor: '#EFF6FF', justifyContent: 'center', alignItems: 'center', position: 'relative' },
  image: { width: '100%', height: '100%', resizeMode: 'cover' },
  imagePlaceholder: { fontSize: 72 },
  backBtn: { position: 'absolute', top: 52, left: 16, backgroundColor: 'rgba(0,0,0,0.4)', width: 38, height: 38, borderRadius: 19, justifyContent: 'center', alignItems: 'center' },
  content: { padding: Spacing.md },
  stockPill: { alignSelf: 'flex-start', paddingHorizontal: 10, paddingVertical: 3, borderRadius: 20, marginBottom: 8 },
  stockText: { fontSize: 11, fontWeight: '700' },
  category: { fontSize: 11, color: Colors.primary, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 4 },
  name: { fontSize: 20, fontWeight: '800', color: '#1E293B', marginBottom: 4 },
  brand: { fontSize: 13, color: '#64748B', marginBottom: 8 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 10 },
  ratingText: { fontSize: 12, color: '#64748B', marginLeft: 4 },
  priceRow: { flexDirection: 'row', alignItems: 'baseline', gap: 8, marginBottom: 12 },
  price: { fontSize: 26, fontWeight: '800', color: Colors.primary },
  unit: { fontSize: 12, color: '#94A3B8' },
  qtyRow: { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 16, backgroundColor: '#F8FAFC', borderRadius: 10, padding: 10 },
  qtyLabel: { fontSize: 13, color: '#64748B', fontWeight: '600' },
  qtyBtn: { width: 32, height: 32, borderRadius: 8, backgroundColor: Colors.primary, justifyContent: 'center', alignItems: 'center' },
  qtyBtnText: { color: Colors.white, fontSize: 18, fontWeight: '700', lineHeight: 20 },
  qtyVal: { fontSize: 18, fontWeight: '700', color: '#1E293B', minWidth: 30, textAlign: 'center' },
  stockCount: { fontSize: 11, color: '#94A3B8', marginLeft: 'auto' },
  tabs: { flexDirection: 'row', borderBottomWidth: 2, borderBottomColor: '#E2E8F0', marginBottom: 16 },
  tab: { flex: 1, paddingVertical: 10, alignItems: 'center' },
  tabActive: { borderBottomWidth: 2, borderBottomColor: Colors.primary, marginBottom: -2 },
  tabText: { fontSize: 13, color: '#94A3B8', fontWeight: '600' },
  tabTextActive: { color: Colors.primary },
  tabContent: { minHeight: 100 },
  desc: { fontSize: 14, color: '#475569', lineHeight: 22 },
  infoBox: { backgroundColor: '#F8FAFC', borderRadius: 10, padding: 12, marginTop: 10 },
  infoBoxTitle: { fontSize: 12, fontWeight: '700', color: '#64748B', marginBottom: 4, textTransform: 'uppercase', letterSpacing: 0.4 },
  infoBoxText: { fontSize: 13, color: '#475569' },
  reviewCard: { backgroundColor: '#F8FAFC', borderRadius: 10, padding: 12, marginBottom: 8 },
  reviewName: { fontSize: 13, fontWeight: '700', color: '#1E293B' },
  reviewText: { fontSize: 13, color: '#64748B', marginTop: 4 },
  bottomBar: { position: 'absolute', bottom: 0, left: 0, right: 0, backgroundColor: Colors.white, borderTopWidth: 1, borderTopColor: '#E2E8F0', padding: Spacing.md, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingBottom: 28 },
  bottomPrice: { fontSize: 20, fontWeight: '800', color: '#1E293B' },
  bottomQty: { fontSize: 11, color: '#94A3B8' },
  addBtn: { backgroundColor: Colors.primary, paddingHorizontal: 24, paddingVertical: 13, borderRadius: 12, minWidth: 160, alignItems: 'center' },
  addBtnDisabled: { backgroundColor: '#CBD5E1' },
  addBtnText: { color: Colors.white, fontSize: 15, fontWeight: '700' },
});
