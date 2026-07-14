import React, { useEffect, useState } from 'react';
import {
  View, Text, FlatList, StyleSheet, TouchableOpacity,
  ActivityIndicator, Alert, TextInput,
} from 'react-native';
import { useRouter } from 'expo-router';
import { cartAPI, ordersAPI } from '../../lib/api';
import { Colors, Spacing, Shadows } from '../../constants/Theme';

const PAYMENT_METHODS = [
  { id: 'paystack', label: '💳 Paystack', sub: 'Card, Bank Transfer, USSD' },
  { id: 'flutterwave', label: '💰 Flutterwave', sub: 'Card, Wallet, Mobile Money' },
  { id: 'transfer', label: 'Bank Transfer', sub: 'Choose bank, wallet, or transfer channel' },
];

const TRANSFER_CHANNELS = [
  { id: 'gtbank', label: 'GTBank' },
  { id: 'access', label: 'Access Bank' },
  { id: 'uba', label: 'UBA' },
  { id: 'firstbank', label: 'First Bank' },
  { id: 'zenith', label: 'Zenith Bank' },
  { id: 'opay', label: 'OPay Wallet' },
  { id: 'palmpay', label: 'PalmPay Wallet' },
  { id: 'kuda', label: 'Kuda' },
  { id: 'moniepoint', label: 'Moniepoint' },
  { id: 'bank_app', label: 'Other Bank App' },
  { id: 'pos', label: 'POS Transfer' },
];

export default function CartScreen() {
  const router = useRouter();
  const [items, setItems]         = useState([]);
  const [total, setTotal]         = useState(0);
  const [loading, setLoading]     = useState(true);
  const [step, setStep]           = useState('cart'); // cart | checkout | success
  const [payMethod, setPayMethod] = useState('paystack');
  const [paymentChannel, setPaymentChannel] = useState('');
  const [address, setAddress]     = useState('');
  const [placing, setPlacing]     = useState(false);
  const [orders, setOrders]       = useState([]);

  const loadCart = async () => {
    try {
      const res = await cartAPI.get();
      setItems(res.data || []);
      setTotal(res.total || 0);
    } catch {}
    finally { setLoading(false); }
  };

  useEffect(() => { loadCart(); }, []);

  const updateQty = async (item, delta) => {
    const newQty = item.quantity + delta;
    if (newQty < 1) return removeItem(item);
    try {
      await cartAPI.update(item.id, newQty);
      setItems(prev => prev.map(i => i.id === item.id ? { ...i, quantity: newQty, line_total: newQty * i.product.selling_price } : i));
      setTotal(prev => prev + (delta * item.product.selling_price));
    } catch {}
  };

  const removeItem = async (item) => {
    try {
      await cartAPI.remove(item.id);
      setItems(prev => prev.filter(i => i.id !== item.id));
      setTotal(prev => prev - item.line_total);
    } catch {}
  };

  const clearCart = () => {
    Alert.alert('Clear Cart', 'Remove all items?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Clear', style: 'destructive', onPress: async () => {
        await cartAPI.clear();
        setItems([]);
        setTotal(0);
      }},
    ]);
  };

  const placeOrder = async () => {
    if (payMethod === 'transfer' && !paymentChannel) {
      Alert.alert(
        'Select Transfer Channel',
        'Select the bank, wallet, or transfer channel you want to use before placing this order.'
      );
      return;
    }

    setPlacing(true);
    try {
      const res = await ordersAPI.checkout({
        payment_method: payMethod,
        payment_channel: payMethod === 'transfer' ? paymentChannel : null,
        delivery_address: address,
      });
      setOrders(res.data || []);
      setStep('success');
    } catch (e) {
      Alert.alert('Order Failed', e.message || 'Could not place order. Please try again.');
    } finally {
      setPlacing(false);
    }
  };

  const tax      = total * 0.075;
  const grandTotal = total + tax;

  if (loading) return <View style={styles.loader}><ActivityIndicator size="large" color={Colors.primary} /></View>;

  // ── Success screen ──────────────────────────────────────────────────────────
  if (step === 'success') {
    return (
      <View style={styles.successWrap}>
        <Text style={styles.successIcon}>✅</Text>
        <Text style={styles.successTitle}>Order Placed!</Text>
        <Text style={styles.successSub}>Your order has been placed successfully. The dealer will confirm shortly.</Text>
        {orders.map(o => (
          <View key={o.id} style={styles.orderCard}>
            <Text style={styles.orderNum}>{o.order_number}</Text>
            <Text style={styles.orderTotal}>₦{Number(o.total).toLocaleString()}</Text>
            <View style={[styles.statusPill, { backgroundColor: '#DBEAFE' }]}>
              <Text style={{ color: '#1D4ED8', fontSize: 11, fontWeight: '700' }}>Pending</Text>
            </View>
          </View>
        ))}
        <TouchableOpacity style={styles.continuBtn} onPress={() => router.replace('/(tabs)/market')}>
          <Text style={styles.continubtnText}>Continue Shopping</Text>
        </TouchableOpacity>
        <TouchableOpacity onPress={() => router.push('/market/orders')} style={{ marginTop: 12 }}>
          <Text style={{ color: Colors.primary, fontWeight: '600', textAlign: 'center' }}>View My Orders →</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Checkout screen ─────────────────────────────────────────────────────────
  if (step === 'checkout') {
    return (
      <View style={{ flex: 1, backgroundColor: '#F8FAFC' }}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => setStep('cart')}><Text style={styles.backText}>←</Text></TouchableOpacity>
          <Text style={styles.headerTitle}>Checkout</Text>
          <View style={{ width: 40 }} />
        </View>
        <FlatList
          contentContainerStyle={{ padding: Spacing.md, paddingBottom: 140 }}
          data={[]}
          ListHeaderComponent={
            <>
              <Text style={styles.sectionTitle}>Order Summary</Text>
              {items.map(item => (
                <View key={item.id} style={styles.summaryRow}>
                  <Text style={styles.summaryName}>{item.product?.name}</Text>
                  <Text style={styles.summaryQty}>×{item.quantity}</Text>
                  <Text style={styles.summaryPrice}>₦{Number(item.line_total).toLocaleString()}</Text>
                </View>
              ))}
              <View style={styles.divider} />
              <View style={styles.summaryRow}><Text style={styles.summaryLabel}>Subtotal</Text><Text style={styles.summaryPrice}>₦{total.toLocaleString()}</Text></View>
              <View style={styles.summaryRow}><Text style={styles.summaryLabel}>VAT (7.5%)</Text><Text style={styles.summaryPrice}>₦{tax.toFixed(2)}</Text></View>
              <View style={[styles.summaryRow, { marginTop: 4 }]}><Text style={[styles.summaryLabel, { fontWeight: '700', color: '#1E293B' }]}>Total</Text><Text style={[styles.summaryPrice, { color: Colors.primary, fontWeight: '800', fontSize: 18 }]}>₦{grandTotal.toFixed(2)}</Text></View>

              <Text style={[styles.sectionTitle, { marginTop: 20 }]}>Payment Method</Text>
              {PAYMENT_METHODS.map(p => (
                <TouchableOpacity key={p.id} style={[styles.payCard, payMethod === p.id && styles.payCardActive]} onPress={() => { setPayMethod(p.id); if (p.id !== 'transfer') setPaymentChannel(''); }}>
                  <View style={[styles.payRadio, payMethod === p.id && { borderColor: Colors.primary }]}>
                    {payMethod === p.id && <View style={styles.payRadioInner} />}
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.payLabel}>{p.label}</Text>
                    <Text style={styles.paySub}>{p.sub}</Text>
                  </View>
                </TouchableOpacity>
              ))}

              {payMethod === 'transfer' && (
                <View style={styles.channelBox}>
                  <Text style={styles.channelTitle}>Select Bank or Wallet</Text>
                  <Text style={styles.channelHint}>Choose the supported channel you will use for this bank transfer.</Text>
                  <View style={styles.channelGrid}>
                    {TRANSFER_CHANNELS.map(channel => (
                      <TouchableOpacity
                        key={channel.id}
                        style={[styles.channelChip, paymentChannel === channel.id && styles.channelChipActive]}
                        onPress={() => setPaymentChannel(channel.id)}
                      >
                        <Text style={[styles.channelText, paymentChannel === channel.id && styles.channelTextActive]}>{channel.label}</Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                </View>
              )}

              <Text style={[styles.sectionTitle, { marginTop: 20 }]}>Delivery Address</Text>
              <TextInput
                style={styles.addressInput}
                value={address}
                onChangeText={setAddress}
                placeholder="Enter your delivery address (optional)"
                multiline
                numberOfLines={3}
                placeholderTextColor="#94A3B8"
              />
            </>
          }
          renderItem={null}
        />
        <View style={styles.bottomBar}>
          <TouchableOpacity style={styles.placeBtn} onPress={placeOrder} disabled={placing}>
            {placing
              ? <ActivityIndicator color={Colors.white} />
              : <Text style={styles.placeBtnText}>Place Order · ₦{grandTotal.toFixed(2)}</Text>
            }
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  // ── Cart screen ─────────────────────────────────────────────────────────────
  return (
    <View style={{ flex: 1, backgroundColor: '#F8FAFC' }}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()}><Text style={styles.backText}>←</Text></TouchableOpacity>
        <Text style={styles.headerTitle}>My Cart ({items.length})</Text>
        {items.length > 0
          ? <TouchableOpacity onPress={clearCart}><Text style={{ color: Colors.danger, fontSize: 13, fontWeight: '600' }}>Clear</Text></TouchableOpacity>
          : <View style={{ width: 40 }} />
        }
      </View>

      <FlatList
        data={items}
        keyExtractor={item => String(item.id)}
        contentContainerStyle={{ padding: Spacing.md, paddingBottom: 120 }}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={{ fontSize: 48, marginBottom: 12 }}>🛒</Text>
            <Text style={{ fontSize: 18, fontWeight: '700', color: '#334155' }}>Your cart is empty</Text>
            <TouchableOpacity onPress={() => router.back()} style={{ marginTop: 16 }}>
              <Text style={{ color: Colors.primary, fontWeight: '600' }}>Browse Products →</Text>
            </TouchableOpacity>
          </View>
        }
        renderItem={({ item }) => (
          <View style={styles.cartCard}>
            <View style={{ flex: 1 }}>
              <Text style={styles.cartName}>{item.product?.name}</Text>
              <Text style={styles.cartUnit}>{item.product?.unit} · {item.product?.brand}</Text>
              <Text style={styles.cartUnitPrice}>₦{Number(item.product?.selling_price).toLocaleString()} each</Text>
            </View>
            <View style={styles.cartRight}>
              <View style={styles.qtyRow}>
                <TouchableOpacity style={styles.qtyBtn} onPress={() => updateQty(item, -1)}>
                  <Text style={styles.qtyBtnText}>−</Text>
                </TouchableOpacity>
                <Text style={styles.qtyVal}>{item.quantity}</Text>
                <TouchableOpacity style={styles.qtyBtn} onPress={() => updateQty(item, 1)}>
                  <Text style={styles.qtyBtnText}>+</Text>
                </TouchableOpacity>
              </View>
              <Text style={styles.cartLineTotal}>₦{Number(item.line_total).toLocaleString()}</Text>
              <TouchableOpacity onPress={() => removeItem(item)} style={{ marginTop: 6 }}>
                <Text style={{ color: Colors.danger, fontSize: 12 }}>Remove</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
      />

      {items.length > 0 && (
        <View style={styles.bottomBar}>
          <View style={{ marginBottom: 8 }}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
              <Text style={{ color: '#64748B', fontSize: 13 }}>Subtotal</Text>
              <Text style={{ fontSize: 13, color: '#1E293B' }}>₦{total.toLocaleString()}</Text>
            </View>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginTop: 2 }}>
              <Text style={{ color: '#64748B', fontSize: 13 }}>VAT (7.5%)</Text>
              <Text style={{ fontSize: 13, color: '#1E293B' }}>₦{tax.toFixed(2)}</Text>
            </View>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginTop: 4 }}>
              <Text style={{ fontWeight: '700', color: '#1E293B' }}>Total</Text>
              <Text style={{ fontWeight: '800', color: Colors.primary, fontSize: 18 }}>₦{grandTotal.toFixed(2)}</Text>
            </View>
          </View>
          <TouchableOpacity style={styles.checkoutBtn} onPress={() => setStep('checkout')}>
            <Text style={styles.checkoutBtnText}>Proceed to Checkout →</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  loader: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: Spacing.md, paddingTop: 52, paddingBottom: 12, backgroundColor: Colors.white, borderBottomWidth: 1, borderBottomColor: '#E2E8F0' },
  backText: { fontSize: 22, color: Colors.primary, width: 40 },
  headerTitle: { fontSize: 17, fontWeight: '700', color: '#1E293B' },
  empty: { alignItems: 'center', paddingTop: 80 },
  cartCard: { flexDirection: 'row', backgroundColor: Colors.white, borderRadius: 12, padding: 12, marginBottom: 10, ...Shadows.sm },
  cartName: { fontSize: 14, fontWeight: '700', color: '#1E293B' },
  cartUnit: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  cartUnitPrice: { fontSize: 12, color: '#64748B', marginTop: 2 },
  cartRight: { alignItems: 'flex-end', justifyContent: 'center', marginLeft: 12 },
  cartLineTotal: { fontSize: 15, fontWeight: '700', color: Colors.primary, marginTop: 6 },
  qtyRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  qtyBtn: { width: 28, height: 28, borderRadius: 6, backgroundColor: Colors.primary, justifyContent: 'center', alignItems: 'center' },
  qtyBtnText: { color: Colors.white, fontSize: 16, fontWeight: '700', lineHeight: 18 },
  qtyVal: { fontSize: 15, fontWeight: '700', minWidth: 24, textAlign: 'center', color: '#1E293B' },
  bottomBar: { backgroundColor: Colors.white, borderTopWidth: 1, borderTopColor: '#E2E8F0', padding: Spacing.md, paddingBottom: 28 },
  checkoutBtn: { backgroundColor: Colors.primary, borderRadius: 12, padding: 14, alignItems: 'center' },
  checkoutBtnText: { color: Colors.white, fontSize: 15, fontWeight: '700' },
  sectionTitle: { fontSize: 15, fontWeight: '700', color: '#1E293B', marginBottom: 10 },
  summaryRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  summaryName: { flex: 1, fontSize: 13, color: '#64748B' },
  summaryQty: { fontSize: 13, color: '#64748B', marginRight: 8 },
  summaryPrice: { fontSize: 13, fontWeight: '600', color: '#1E293B', minWidth: 80, textAlign: 'right' },
  summaryLabel: { flex: 1, fontSize: 13, color: '#64748B' },
  divider: { height: 1, backgroundColor: '#E2E8F0', marginVertical: 8 },
  payCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: Colors.white, borderRadius: 12, borderWidth: 1.5, borderColor: '#E2E8F0', padding: 14, marginBottom: 8 },
  payCardActive: { borderColor: Colors.primary, backgroundColor: '#EFF6FF' },
  payRadio: { width: 20, height: 20, borderRadius: 10, borderWidth: 2, borderColor: '#CBD5E1', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  payRadioInner: { width: 10, height: 10, borderRadius: 5, backgroundColor: Colors.primary },
  payLabel: { fontSize: 14, fontWeight: '600', color: '#1E293B' },
  paySub: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  channelBox: { backgroundColor: Colors.white, borderRadius: 12, borderWidth: 1, borderColor: '#BBF7D0', padding: 12, marginTop: 4, marginBottom: 12 },
  channelTitle: { fontSize: 14, fontWeight: '700', color: '#14532D' },
  channelHint: { fontSize: 11, color: '#64748B', marginTop: 3, marginBottom: 10 },
  channelGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  channelChip: { minHeight: 38, borderRadius: 10, borderWidth: 1, borderColor: '#CBD5E1', paddingHorizontal: 12, justifyContent: 'center', backgroundColor: '#F8FAFC' },
  channelChipActive: { borderColor: Colors.primary, backgroundColor: '#DCFCE7' },
  channelText: { fontSize: 12, fontWeight: '600', color: '#334155' },
  channelTextActive: { color: '#166534' },
  addressInput: { backgroundColor: Colors.white, borderRadius: 12, borderWidth: 1, borderColor: '#E2E8F0', padding: 12, fontSize: 14, color: '#1E293B', minHeight: 80, textAlignVertical: 'top' },
  placeBtn: { backgroundColor: Colors.primary, borderRadius: 12, padding: 15, alignItems: 'center' },
  placeBtnText: { color: Colors.white, fontSize: 15, fontWeight: '700' },
  successWrap: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: Spacing.lg, backgroundColor: '#F8FAFC' },
  successIcon: { fontSize: 64, marginBottom: 16 },
  successTitle: { fontSize: 24, fontWeight: '800', color: '#1E293B', marginBottom: 8 },
  successSub: { fontSize: 14, color: '#64748B', textAlign: 'center', marginBottom: 24 },
  orderCard: { backgroundColor: Colors.white, borderRadius: 12, padding: 16, marginBottom: 10, width: '100%', flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  orderNum: { fontSize: 13, fontWeight: '700', color: '#1E293B' },
  orderTotal: { fontSize: 14, fontWeight: '800', color: Colors.primary },
  statusPill: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8 },
  continuBtn: { backgroundColor: Colors.primary, borderRadius: 12, paddingVertical: 13, paddingHorizontal: 40, marginTop: 16 },
  continubtnText: { color: Colors.white, fontSize: 15, fontWeight: '700' },
});

