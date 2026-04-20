/**
 * Bookcabin POS — IndexedDB Offline Store
 *
 * Menyimpan transaksi POS ke IndexedDB saat offline,
 * kemudian sync ke server saat koneksi pulih.
 */
const OfflineStore = {
    DB_NAME: 'bookcabin_pos',
    DB_VERSION: 1,
    STORE_NAME: 'pending_transactions',
    db: null,

    /**
     * Inisialisasi IndexedDB
     */
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            request.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(this.STORE_NAME)) {
                    const store = db.createObjectStore(this.STORE_NAME, { keyPath: 'offline_id' });
                    store.createIndex('created_at', 'created_at', { unique: false });
                    store.createIndex('synced', 'synced', { unique: false });
                }
            };

            request.onsuccess = (e) => {
                this.db = e.target.result;
                console.log('[OfflineStore] IndexedDB ready');
                resolve(this.db);
            };

            request.onerror = (e) => {
                console.error('[OfflineStore] IndexedDB error:', e.target.error);
                reject(e.target.error);
            };
        });
    },

    /**
     * Simpan transaksi ke IndexedDB (saat offline)
     */
    async save(transaction) {
        const tx = {
            offline_id: `offline-${Date.now()}-${Math.random().toString(36).substr(2, 6)}`,
            ...transaction,
            created_at: new Date().toISOString(),
            synced: false,
        };

        return new Promise((resolve, reject) => {
            const txn = this.db.transaction(this.STORE_NAME, 'readwrite');
            const store = txn.objectStore(this.STORE_NAME);
            const request = store.add(tx);

            request.onsuccess = () => {
                console.log('[OfflineStore] Saved:', tx.offline_id);
                resolve(tx);
            };
            request.onerror = (e) => reject(e.target.error);
        });
    },

    /**
     * Ambil semua transaksi pending (belum disync)
     */
    async getPending() {
        return new Promise((resolve, reject) => {
            const txn = this.db.transaction(this.STORE_NAME, 'readonly');
            const store = txn.objectStore(this.STORE_NAME);
            const index = store.index('synced');
            const request = index.getAll(false);

            request.onsuccess = () => resolve(request.result);
            request.onerror = (e) => reject(e.target.error);
        });
    },

    /**
     * Hitung jumlah transaksi pending
     */
    async countPending() {
        const pending = await this.getPending();
        return pending.length;
    },

    /**
     * Tandai transaksi sebagai sudah disync
     */
    async markSynced(offlineId) {
        return new Promise((resolve, reject) => {
            const txn = this.db.transaction(this.STORE_NAME, 'readwrite');
            const store = txn.objectStore(this.STORE_NAME);
            const request = store.get(offlineId);

            request.onsuccess = () => {
                const item = request.result;
                if (item) {
                    item.synced = true;
                    item.synced_at = new Date().toISOString();
                    store.put(item);
                }
                resolve();
            };
            request.onerror = (e) => reject(e.target.error);
        });
    },

    /**
     * Hapus transaksi yang sudah disync (cleanup)
     */
    async clearSynced() {
        const txn = this.db.transaction(this.STORE_NAME, 'readwrite');
        const store = txn.objectStore(this.STORE_NAME);
        const index = store.index('synced');
        const request = index.openCursor(true);

        return new Promise((resolve) => {
            request.onsuccess = (e) => {
                const cursor = e.target.result;
                if (cursor) {
                    cursor.delete();
                    cursor.continue();
                } else {
                    resolve();
                }
            };
        });
    },

    /**
     * Sync semua transaksi pending ke server
     */
    async syncAll(csrfToken) {
        const pending = await this.getPending();
        if (pending.length === 0) {
            console.log('[OfflineStore] No pending transactions to sync');
            return { synced: 0, skipped: 0, errors: [] };
        }

        console.log(`[OfflineStore] Syncing ${pending.length} transactions...`);

        try {
            const res = await fetch('/api/transactions/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ transactions: pending }),
            });

            if (!res.ok) {
                throw new Error(`Sync failed: ${res.status}`);
            }

            const data = await res.json();

            // Tandai semua sebagai synced
            for (const tx of pending) {
                await this.markSynced(tx.offline_id);
            }

            // Cleanup
            await this.clearSynced();

            console.log('[OfflineStore] Sync complete:', data.results);
            return data.results;
        } catch (e) {
            console.error('[OfflineStore] Sync error:', e);
            throw e;
        }
    },
};

/**
 * Network Status Manager
 * Deteksi online/offline dan trigger auto-sync
 */
const NetworkManager = {
    isOnline: navigator.onLine,
    listeners: [],

    init() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            console.log('[Network] Back online — triggering sync');
            this.notify();
            this.autoSync();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            console.log('[Network] Offline');
            this.notify();
        });
    },

    onChange(callback) {
        this.listeners.push(callback);
    },

    notify() {
        this.listeners.forEach(cb => cb(this.isOnline));
    },

    async autoSync() {
        if (!this.isOnline) return;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                await OfflineStore.syncAll(csrfToken);
            }
        } catch (e) {
            console.error('[Network] Auto-sync failed:', e);
        }
    },
};

// Export for Alpine.js usage
window.OfflineStore = OfflineStore;
window.NetworkManager = NetworkManager;
