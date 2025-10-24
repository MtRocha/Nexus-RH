from sqlalchemy import text
from sqlalchemy.orm import sessionmaker
from repositories.db_proc import SessionLocal, get_db

SessionLocal = get_db()

result = SessionLocal.execute(text("SELECT 1")).fetchone()
print(result)
