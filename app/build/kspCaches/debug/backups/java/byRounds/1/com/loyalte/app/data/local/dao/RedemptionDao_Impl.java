package com.loyalte.app.data.local.dao;

import android.database.Cursor;
import androidx.annotation.NonNull;
import androidx.room.CoroutinesRoom;
import androidx.room.EntityInsertionAdapter;
import androidx.room.RoomDatabase;
import androidx.room.RoomSQLiteQuery;
import androidx.room.SharedSQLiteStatement;
import androidx.room.util.CursorUtil;
import androidx.room.util.DBUtil;
import androidx.sqlite.db.SupportSQLiteStatement;
import com.loyalte.app.data.local.entity.RedemptionEntity;
import java.lang.Class;
import java.lang.Exception;
import java.lang.Object;
import java.lang.Override;
import java.lang.String;
import java.lang.SuppressWarnings;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.Callable;
import javax.annotation.processing.Generated;
import kotlin.Unit;
import kotlin.coroutines.Continuation;
import kotlinx.coroutines.flow.Flow;

@Generated("androidx.room.RoomProcessor")
@SuppressWarnings({"unchecked", "deprecation"})
public final class RedemptionDao_Impl implements RedemptionDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<RedemptionEntity> __insertionAdapterOfRedemptionEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public RedemptionDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfRedemptionEntity = new EntityInsertionAdapter<RedemptionEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR REPLACE INTO `redemptions` (`id`,`customerId`,`rewardId`,`rewardName`,`pointsSpent`,`redeemedAt`) VALUES (?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final RedemptionEntity entity) {
        statement.bindString(1, entity.getId());
        statement.bindString(2, entity.getCustomerId());
        statement.bindString(3, entity.getRewardId());
        statement.bindString(4, entity.getRewardName());
        statement.bindLong(5, entity.getPointsSpent());
        statement.bindLong(6, entity.getRedeemedAt());
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM redemptions";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final RedemptionEntity redemption,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __insertionAdapterOfRedemptionEntity.insert(redemption);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object insertAll(final List<RedemptionEntity> redemptions,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __insertionAdapterOfRedemptionEntity.insert(redemptions);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteAll(final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteAll.acquire();
        try {
          __db.beginTransaction();
          try {
            _stmt.executeUpdateDelete();
            __db.setTransactionSuccessful();
            return Unit.INSTANCE;
          } finally {
            __db.endTransaction();
          }
        } finally {
          __preparedStmtOfDeleteAll.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Flow<List<RedemptionEntity>> getByCustomer(final String customerId) {
    final String _sql = "SELECT * FROM redemptions WHERE customerId = ? ORDER BY redeemedAt DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindString(_argIndex, customerId);
    return CoroutinesRoom.createFlow(__db, false, new String[] {"redemptions"}, new Callable<List<RedemptionEntity>>() {
      @Override
      @NonNull
      public List<RedemptionEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfCustomerId = CursorUtil.getColumnIndexOrThrow(_cursor, "customerId");
          final int _cursorIndexOfRewardId = CursorUtil.getColumnIndexOrThrow(_cursor, "rewardId");
          final int _cursorIndexOfRewardName = CursorUtil.getColumnIndexOrThrow(_cursor, "rewardName");
          final int _cursorIndexOfPointsSpent = CursorUtil.getColumnIndexOrThrow(_cursor, "pointsSpent");
          final int _cursorIndexOfRedeemedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "redeemedAt");
          final List<RedemptionEntity> _result = new ArrayList<RedemptionEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final RedemptionEntity _item;
            final String _tmpId;
            _tmpId = _cursor.getString(_cursorIndexOfId);
            final String _tmpCustomerId;
            _tmpCustomerId = _cursor.getString(_cursorIndexOfCustomerId);
            final String _tmpRewardId;
            _tmpRewardId = _cursor.getString(_cursorIndexOfRewardId);
            final String _tmpRewardName;
            _tmpRewardName = _cursor.getString(_cursorIndexOfRewardName);
            final int _tmpPointsSpent;
            _tmpPointsSpent = _cursor.getInt(_cursorIndexOfPointsSpent);
            final long _tmpRedeemedAt;
            _tmpRedeemedAt = _cursor.getLong(_cursorIndexOfRedeemedAt);
            _item = new RedemptionEntity(_tmpId,_tmpCustomerId,_tmpRewardId,_tmpRewardName,_tmpPointsSpent,_tmpRedeemedAt);
            _result.add(_item);
          }
          return _result;
        } finally {
          _cursor.close();
        }
      }

      @Override
      protected void finalize() {
        _statement.release();
      }
    });
  }

  @NonNull
  public static List<Class<?>> getRequiredConverters() {
    return Collections.emptyList();
  }
}
