import React, { useEffect, useState } from 'react';
import {
  View, Text, FlatList, StyleSheet, TouchableOpacity,
  ActivityIndicator, RefreshControl,
} from 'react-native';
import { useRouter } from 'expo-router';
import { ordersAPI } from '../../lib/api';
import { Colors, Spacing, Shadows } from '../../constants/Theme';

const STATUS_STYLES = {
  pending:    { bg: '#DBEAFE', color: '#1D4ED8', label: 'Pending' },
  confirmed:  { bg: '#D1FAE5', color: '#065F46', label: 'Confirmed' },
  processing: { bg: '#FEF3C7', color: '#92400E', label: 'Processing' },
  shipped:    { bg: '#EDE9FE', color: '#5B21B6', label: 'Shipped' },
  delivered:  { bg: '#D1FAE5', color: '#065F46', label: 'Delivered' },
  cancelled:  { bg: '#FEE2E2', color: '#991B1B', label: 'Cancelled' },
};

export default function OrdersScreen() {
  const router = useRouter();
  const [orders, setOrders]       = useState([]);
  const [loading, setLoading]     = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadOrders = async (silent = false) => {
    if (!silent) setLoading(true);
    try {
      const res = await ordersAPI.list();
      setOrders(res.data || []);
    } catch {}
    finally { setLoading(false); setRefreshing(false); }
  };

  useEffect(() => { loadOrders(); }, []);

  const cancelOrder = async (order) => {
    try {
      await ordersAPI.cancel(order.id);
      setOrders(prev => prev.map(o => o.id === order.id ? { ...o, status: 'cancelled' } : o));
    } catch (e) {
      alert(e.message || 'Could not cancel order');
    }
  };

  if (loading) return <View style={styles.loader}><ActivityIndicator size="large" color={Colors.primary} /></View>;

  return (
    <View style={{ flex: 1, backgroundColor: '#F8FAFC' }}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()}><Text style={styles.backText}>←</Text></TouchableOpacity>
        <Text style={styles.headerTitle}>My Orders</Text>
        <View style={{ width: 40 }} />
      </View>

      <FlatList
        data={orders}
        keyExtractor={item => String(item.id)}
        contentContainerStyle={{ padding: Spacing.md, paddingBottom: 40 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); loadOrders(true); }} tintColor={Colors.primary} />}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={{ fontSize: 48, marginBottom: 12 }}>📦</Text>
            <Text style={{ fontSize: 18, fontWeight: '700', color: '#334155' }}>No orders yet</Text>
            <TouchableOpacity onPress={() => router.replace('/(tabs)/market')} style={{ marginTop: 16 }}>
              <Text style={{ color: Colors.primary, fontWeight: '600' }}>Browse Products →</Text>
            </TouchableOpacity>
          </View>
        }
        renderItem={({ item }) => {
          const st = STATUS_STYLES[item.status] || STATUS_STYLES.pending;
          return (
            <View style={styles.orderCard}>
              <View style={styles.orderTopRow}>
                <Text style={styles.orderNum}>{item.order_number}</Text>
                <View style={[styles.statusPill, { backgroundColor: st.bg }]}>
                  <Text style={[styles.statusText, { color: st.color }]}>{st.label}</Text>
                </View>
              </View>

              <Text style={styles.orderDate}>{new Date(item.created_at).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })}</Text>

              {item.items?.map(oi => (
                <View key={oi.id} style={styles.itemRow}>
                  <Text style={styles.itemName}>{oi.product_name}</Text>
                  <Text style={styles.itemQty}>×{oi.quantity}</Text>
                  <Text style={styles.itemPrice}>₦{Number(oi.total_price).toLocaleString()}</Text>
                </View>
              ))}

              <View style={styles.orderFooter}>
                <View>
                  <Text style={styles.totalLabel}>Total (incl. VAT)</Text>
                  <Text style={styles.totalAmount}>₦{Number(item.total).toLocaleString()}</Text>
                </View>
                <View style={{ alignItems: 'flex-end', gap: 6 }}>
                  {item.payment_status === 'paid'
                    ? <View style={styles.paidBadge}><Text style={{ color: '#065F46', fontSize: 11, fontWeight: '700' }}>✓ Paid</Text></View>
                    : <View style={[styles.paidBadge, { backgroundColor: '#FEF3C7' }]}><Text style={{ color: '#92400E', fontSize: 11, fontWeight: '700' }}>Unpaid</Text></View>
                  }
                  {item.status === 'pending' && (
                    <TouchableOpacity onPress={() => cancelOrder(item)}>
                      <Text style={{ color: Colors.danger, fontSize: 12, fontWeight: '600' }}>Cancel</Text>
                    </TouchableOpacity>
                  )}
                </View>
              </View>
            </View>
          );
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  loader: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: Spacing.md, paddingTop: 52, paddingBottom: 12, backgroundColor: Colors.white, borderBottomWidth: 1, borderBottomColor: '#E2E8F0' },
  backText: { fontSize: 22, color: Colors.primary, width: 40 },
  headerTitle: { fontSize: 17, fontWeight: '700', color: '#1E293B' },
  empty: { alignItems: 'center', paddingTop: 80 },
  orderCard: { backgroundColor: Colors.white, borderRadius: 14, padding: 14, marginBottom: 12, ...Shadows.sm },
  orderTopRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  orderNum: { fontSize: 13, fontWeight: '700', color: '#1E293B' },
  statusPill: { paddingHorizontal: 9, paddingVertical: 3, borderRadius: 8 },
  statusText: { fontSize: 11, fontWeight: '700' },
  orderDate: { fontSize: 11, color: '#94A3B8', marginBottom: 10 },
  itemRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 4, borderTopWidth: 1, borderTopColor: '#F1F5F9' },
  itemName: { flex: 1, fontSize: 13, color: '#475569' },
  itemQty: { fontSize: 13, color: '#94A3B8', marginRight: 8 },
  itemPrice: { fontSize: 13, fontWeight: '600', color: '#1E293B', minWidth: 80, textAlign: 'right' },
  orderFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-end', marginTop: 10, paddingTop: 10, borderTopWidth: 1, borderTopColor: '#E2E8F0' },
  totalLabel: { fontSize: 11, color: '#94A3B8' },
  totalAmount: { fontSize: 18, fontWeight: '800', color: Colors.primary },
  paidBadge: { backgroundColor: '#D1FAE5', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8 },
});
