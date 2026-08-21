import { computed, ref } from 'vue';

export interface CartLine {
    item_id: number;
    name: string;
    price: number;
    photo_url?: string | null;
}

export const cart = ref<Record<number, CartLine & { qty: number }>>({});

export const outletId = ref<number | null>(null);

export const cartCount = computed(() =>
    Object.values(cart.value).reduce((s, l) => s + l.qty, 0),
);

export const cartTotal = computed(() =>
    Object.values(cart.value).reduce((s, l) => s + l.price * l.qty, 0),
);

export const cartLines = computed(() => Object.values(cart.value));

export const addToCart = (item: CartLine) => {
    const line = cart.value[item.item_id];
    if (line) {
        line.qty += 1;
    } else {
        cart.value[item.item_id] = { ...item, qty: 1 };
    }
};

export const setQty = (itemId: number, qty: number) => {
    if (qty <= 0) {
        delete cart.value[itemId];
    } else {
        cart.value[itemId].qty = qty;
    }
};

export const clearCart = () => {
    cart.value = {};
};
