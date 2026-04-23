package com.loyalte.app.data.local;

import androidx.annotation.NonNull;
import androidx.room.DatabaseConfiguration;
import androidx.room.InvalidationTracker;
import androidx.room.RoomDatabase;
import androidx.room.RoomOpenHelper;
import androidx.room.migration.AutoMigrationSpec;
import androidx.room.migration.Migration;
import androidx.room.util.DBUtil;
import androidx.room.util.TableInfo;
import androidx.sqlite.db.SupportSQLiteDatabase;
import androidx.sqlite.db.SupportSQLiteOpenHelper;
import com.loyalte.app.data.local.dao.CustomerDao;
import com.loyalte.app.data.local.dao.CustomerDao_Impl;
import com.loyalte.app.data.local.dao.LoyaltyTransactionDao;
import com.loyalte.app.data.local.dao.LoyaltyTransactionDao_Impl;
import com.loyalte.app.data.local.dao.RedemptionDao;
import com.loyalte.app.data.local.dao.RedemptionDao_Impl;
import com.loyalte.app.data.local.dao.RewardDao;
import com.loyalte.app.data.local.dao.RewardDao_Impl;
import java.lang.Class;
import java.lang.Override;
import java.lang.String;
import java.lang.SuppressWarnings;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import javax.annotation.processing.Generated;

@Generated("androidx.room.RoomProcessor")
@SuppressWarnings({"unchecked", "deprecation"})
public final class LoyalteDatabase_Impl extends LoyalteDatabase {
  private volatile CustomerDao _customerDao;

  private volatile LoyaltyTransactionDao _loyaltyTransactionDao;

  private volatile RewardDao _rewardDao;

  private volatile RedemptionDao _redemptionDao;

  @Override
  @NonNull
  protected SupportSQLiteOpenHelper createOpenHelper(@NonNull final DatabaseConfiguration config) {
    final SupportSQLiteOpenHelper.Callback _openCallback = new RoomOpenHelper(config, new RoomOpenHelper.Delegate(1) {
      @Override
      public void createAllTables(@NonNull final SupportSQLiteDatabase db) {
        db.execSQL("CREATE TABLE IF NOT EXISTS `customers` (`id` TEXT NOT NULL, `memberId` TEXT NOT NULL, `name` TEXT NOT NULL, `phone` TEXT NOT NULL, `email` TEXT, `tier` TEXT NOT NULL, `points` INTEGER NOT NULL, `qrCode` TEXT NOT NULL, `createdAt` INTEGER NOT NULL, `updatedAt` INTEGER NOT NULL, PRIMARY KEY(`id`))");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_customers_phone` ON `customers` (`phone`)");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_customers_memberId` ON `customers` (`memberId`)");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_customers_qrCode` ON `customers` (`qrCode`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `loyalty_transactions` (`id` TEXT NOT NULL, `customerId` TEXT NOT NULL, `type` TEXT NOT NULL, `points` INTEGER NOT NULL, `description` TEXT NOT NULL, `createdAt` INTEGER NOT NULL, PRIMARY KEY(`id`), FOREIGN KEY(`customerId`) REFERENCES `customers`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE )");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_loyalty_transactions_customerId` ON `loyalty_transactions` (`customerId`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `rewards` (`id` TEXT NOT NULL, `name` TEXT NOT NULL, `description` TEXT NOT NULL, `pointsRequired` INTEGER NOT NULL, `isActive` INTEGER NOT NULL, `category` TEXT NOT NULL, `createdAt` INTEGER NOT NULL, PRIMARY KEY(`id`))");
        db.execSQL("CREATE TABLE IF NOT EXISTS `redemptions` (`id` TEXT NOT NULL, `customerId` TEXT NOT NULL, `rewardId` TEXT NOT NULL, `rewardName` TEXT NOT NULL, `pointsSpent` INTEGER NOT NULL, `redeemedAt` INTEGER NOT NULL, PRIMARY KEY(`id`), FOREIGN KEY(`customerId`) REFERENCES `customers`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE )");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_redemptions_customerId` ON `redemptions` (`customerId`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS room_master_table (id INTEGER PRIMARY KEY,identity_hash TEXT)");
        db.execSQL("INSERT OR REPLACE INTO room_master_table (id,identity_hash) VALUES(42, 'bcfa8c8bce70e78f11b31337a2babb70')");
      }

      @Override
      public void dropAllTables(@NonNull final SupportSQLiteDatabase db) {
        db.execSQL("DROP TABLE IF EXISTS `customers`");
        db.execSQL("DROP TABLE IF EXISTS `loyalty_transactions`");
        db.execSQL("DROP TABLE IF EXISTS `rewards`");
        db.execSQL("DROP TABLE IF EXISTS `redemptions`");
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onDestructiveMigration(db);
          }
        }
      }

      @Override
      public void onCreate(@NonNull final SupportSQLiteDatabase db) {
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onCreate(db);
          }
        }
      }

      @Override
      public void onOpen(@NonNull final SupportSQLiteDatabase db) {
        mDatabase = db;
        db.execSQL("PRAGMA foreign_keys = ON");
        internalInitInvalidationTracker(db);
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onOpen(db);
          }
        }
      }

      @Override
      public void onPreMigrate(@NonNull final SupportSQLiteDatabase db) {
        DBUtil.dropFtsSyncTriggers(db);
      }

      @Override
      public void onPostMigrate(@NonNull final SupportSQLiteDatabase db) {
      }

      @Override
      @NonNull
      public RoomOpenHelper.ValidationResult onValidateSchema(
          @NonNull final SupportSQLiteDatabase db) {
        final HashMap<String, TableInfo.Column> _columnsCustomers = new HashMap<String, TableInfo.Column>(10);
        _columnsCustomers.put("id", new TableInfo.Column("id", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("memberId", new TableInfo.Column("memberId", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("name", new TableInfo.Column("name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("phone", new TableInfo.Column("phone", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("email", new TableInfo.Column("email", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("tier", new TableInfo.Column("tier", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("points", new TableInfo.Column("points", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("qrCode", new TableInfo.Column("qrCode", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("createdAt", new TableInfo.Column("createdAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsCustomers.put("updatedAt", new TableInfo.Column("updatedAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysCustomers = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesCustomers = new HashSet<TableInfo.Index>(3);
        _indicesCustomers.add(new TableInfo.Index("index_customers_phone", true, Arrays.asList("phone"), Arrays.asList("ASC")));
        _indicesCustomers.add(new TableInfo.Index("index_customers_memberId", true, Arrays.asList("memberId"), Arrays.asList("ASC")));
        _indicesCustomers.add(new TableInfo.Index("index_customers_qrCode", true, Arrays.asList("qrCode"), Arrays.asList("ASC")));
        final TableInfo _infoCustomers = new TableInfo("customers", _columnsCustomers, _foreignKeysCustomers, _indicesCustomers);
        final TableInfo _existingCustomers = TableInfo.read(db, "customers");
        if (!_infoCustomers.equals(_existingCustomers)) {
          return new RoomOpenHelper.ValidationResult(false, "customers(com.loyalte.app.data.local.entity.CustomerEntity).\n"
                  + " Expected:\n" + _infoCustomers + "\n"
                  + " Found:\n" + _existingCustomers);
        }
        final HashMap<String, TableInfo.Column> _columnsLoyaltyTransactions = new HashMap<String, TableInfo.Column>(6);
        _columnsLoyaltyTransactions.put("id", new TableInfo.Column("id", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsLoyaltyTransactions.put("customerId", new TableInfo.Column("customerId", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsLoyaltyTransactions.put("type", new TableInfo.Column("type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsLoyaltyTransactions.put("points", new TableInfo.Column("points", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsLoyaltyTransactions.put("description", new TableInfo.Column("description", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsLoyaltyTransactions.put("createdAt", new TableInfo.Column("createdAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysLoyaltyTransactions = new HashSet<TableInfo.ForeignKey>(1);
        _foreignKeysLoyaltyTransactions.add(new TableInfo.ForeignKey("customers", "CASCADE", "NO ACTION", Arrays.asList("customerId"), Arrays.asList("id")));
        final HashSet<TableInfo.Index> _indicesLoyaltyTransactions = new HashSet<TableInfo.Index>(1);
        _indicesLoyaltyTransactions.add(new TableInfo.Index("index_loyalty_transactions_customerId", false, Arrays.asList("customerId"), Arrays.asList("ASC")));
        final TableInfo _infoLoyaltyTransactions = new TableInfo("loyalty_transactions", _columnsLoyaltyTransactions, _foreignKeysLoyaltyTransactions, _indicesLoyaltyTransactions);
        final TableInfo _existingLoyaltyTransactions = TableInfo.read(db, "loyalty_transactions");
        if (!_infoLoyaltyTransactions.equals(_existingLoyaltyTransactions)) {
          return new RoomOpenHelper.ValidationResult(false, "loyalty_transactions(com.loyalte.app.data.local.entity.LoyaltyTransactionEntity).\n"
                  + " Expected:\n" + _infoLoyaltyTransactions + "\n"
                  + " Found:\n" + _existingLoyaltyTransactions);
        }
        final HashMap<String, TableInfo.Column> _columnsRewards = new HashMap<String, TableInfo.Column>(7);
        _columnsRewards.put("id", new TableInfo.Column("id", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRewards.put("name", new TableInfo.Column("name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRewards.put("description", new TableInfo.Column("description", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRewards.put("pointsRequired", new TableInfo.Column("pointsRequired", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRewards.put("isActive", new TableInfo.Column("isActive", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRewards.put("category", new TableInfo.Column("category", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRewards.put("createdAt", new TableInfo.Column("createdAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysRewards = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesRewards = new HashSet<TableInfo.Index>(0);
        final TableInfo _infoRewards = new TableInfo("rewards", _columnsRewards, _foreignKeysRewards, _indicesRewards);
        final TableInfo _existingRewards = TableInfo.read(db, "rewards");
        if (!_infoRewards.equals(_existingRewards)) {
          return new RoomOpenHelper.ValidationResult(false, "rewards(com.loyalte.app.data.local.entity.RewardEntity).\n"
                  + " Expected:\n" + _infoRewards + "\n"
                  + " Found:\n" + _existingRewards);
        }
        final HashMap<String, TableInfo.Column> _columnsRedemptions = new HashMap<String, TableInfo.Column>(6);
        _columnsRedemptions.put("id", new TableInfo.Column("id", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRedemptions.put("customerId", new TableInfo.Column("customerId", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRedemptions.put("rewardId", new TableInfo.Column("rewardId", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRedemptions.put("rewardName", new TableInfo.Column("rewardName", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRedemptions.put("pointsSpent", new TableInfo.Column("pointsSpent", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRedemptions.put("redeemedAt", new TableInfo.Column("redeemedAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysRedemptions = new HashSet<TableInfo.ForeignKey>(1);
        _foreignKeysRedemptions.add(new TableInfo.ForeignKey("customers", "CASCADE", "NO ACTION", Arrays.asList("customerId"), Arrays.asList("id")));
        final HashSet<TableInfo.Index> _indicesRedemptions = new HashSet<TableInfo.Index>(1);
        _indicesRedemptions.add(new TableInfo.Index("index_redemptions_customerId", false, Arrays.asList("customerId"), Arrays.asList("ASC")));
        final TableInfo _infoRedemptions = new TableInfo("redemptions", _columnsRedemptions, _foreignKeysRedemptions, _indicesRedemptions);
        final TableInfo _existingRedemptions = TableInfo.read(db, "redemptions");
        if (!_infoRedemptions.equals(_existingRedemptions)) {
          return new RoomOpenHelper.ValidationResult(false, "redemptions(com.loyalte.app.data.local.entity.RedemptionEntity).\n"
                  + " Expected:\n" + _infoRedemptions + "\n"
                  + " Found:\n" + _existingRedemptions);
        }
        return new RoomOpenHelper.ValidationResult(true, null);
      }
    }, "bcfa8c8bce70e78f11b31337a2babb70", "3a1c3f864109e0896b4de7ebeaadeecd");
    final SupportSQLiteOpenHelper.Configuration _sqliteConfig = SupportSQLiteOpenHelper.Configuration.builder(config.context).name(config.name).callback(_openCallback).build();
    final SupportSQLiteOpenHelper _helper = config.sqliteOpenHelperFactory.create(_sqliteConfig);
    return _helper;
  }

  @Override
  @NonNull
  protected InvalidationTracker createInvalidationTracker() {
    final HashMap<String, String> _shadowTablesMap = new HashMap<String, String>(0);
    final HashMap<String, Set<String>> _viewTables = new HashMap<String, Set<String>>(0);
    return new InvalidationTracker(this, _shadowTablesMap, _viewTables, "customers","loyalty_transactions","rewards","redemptions");
  }

  @Override
  public void clearAllTables() {
    super.assertNotMainThread();
    final SupportSQLiteDatabase _db = super.getOpenHelper().getWritableDatabase();
    final boolean _supportsDeferForeignKeys = android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.LOLLIPOP;
    try {
      if (!_supportsDeferForeignKeys) {
        _db.execSQL("PRAGMA foreign_keys = FALSE");
      }
      super.beginTransaction();
      if (_supportsDeferForeignKeys) {
        _db.execSQL("PRAGMA defer_foreign_keys = TRUE");
      }
      _db.execSQL("DELETE FROM `customers`");
      _db.execSQL("DELETE FROM `loyalty_transactions`");
      _db.execSQL("DELETE FROM `rewards`");
      _db.execSQL("DELETE FROM `redemptions`");
      super.setTransactionSuccessful();
    } finally {
      super.endTransaction();
      if (!_supportsDeferForeignKeys) {
        _db.execSQL("PRAGMA foreign_keys = TRUE");
      }
      _db.query("PRAGMA wal_checkpoint(FULL)").close();
      if (!_db.inTransaction()) {
        _db.execSQL("VACUUM");
      }
    }
  }

  @Override
  @NonNull
  protected Map<Class<?>, List<Class<?>>> getRequiredTypeConverters() {
    final HashMap<Class<?>, List<Class<?>>> _typeConvertersMap = new HashMap<Class<?>, List<Class<?>>>();
    _typeConvertersMap.put(CustomerDao.class, CustomerDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(LoyaltyTransactionDao.class, LoyaltyTransactionDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(RewardDao.class, RewardDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(RedemptionDao.class, RedemptionDao_Impl.getRequiredConverters());
    return _typeConvertersMap;
  }

  @Override
  @NonNull
  public Set<Class<? extends AutoMigrationSpec>> getRequiredAutoMigrationSpecs() {
    final HashSet<Class<? extends AutoMigrationSpec>> _autoMigrationSpecsSet = new HashSet<Class<? extends AutoMigrationSpec>>();
    return _autoMigrationSpecsSet;
  }

  @Override
  @NonNull
  public List<Migration> getAutoMigrations(
      @NonNull final Map<Class<? extends AutoMigrationSpec>, AutoMigrationSpec> autoMigrationSpecs) {
    final List<Migration> _autoMigrations = new ArrayList<Migration>();
    return _autoMigrations;
  }

  @Override
  public CustomerDao customerDao() {
    if (_customerDao != null) {
      return _customerDao;
    } else {
      synchronized(this) {
        if(_customerDao == null) {
          _customerDao = new CustomerDao_Impl(this);
        }
        return _customerDao;
      }
    }
  }

  @Override
  public LoyaltyTransactionDao loyaltyTransactionDao() {
    if (_loyaltyTransactionDao != null) {
      return _loyaltyTransactionDao;
    } else {
      synchronized(this) {
        if(_loyaltyTransactionDao == null) {
          _loyaltyTransactionDao = new LoyaltyTransactionDao_Impl(this);
        }
        return _loyaltyTransactionDao;
      }
    }
  }

  @Override
  public RewardDao rewardDao() {
    if (_rewardDao != null) {
      return _rewardDao;
    } else {
      synchronized(this) {
        if(_rewardDao == null) {
          _rewardDao = new RewardDao_Impl(this);
        }
        return _rewardDao;
      }
    }
  }

  @Override
  public RedemptionDao redemptionDao() {
    if (_redemptionDao != null) {
      return _redemptionDao;
    } else {
      synchronized(this) {
        if(_redemptionDao == null) {
          _redemptionDao = new RedemptionDao_Impl(this);
        }
        return _redemptionDao;
      }
    }
  }
}
