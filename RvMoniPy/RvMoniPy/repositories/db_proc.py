from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from config.settings import DB_CONNECTION_STRING


engine = create_engine(DB_CONNECTION_STRING)
SessionLocal = sessionmaker(bind=engine)

def get_db():
    print (DB_CONNECTION_STRING)
    return SessionLocal()