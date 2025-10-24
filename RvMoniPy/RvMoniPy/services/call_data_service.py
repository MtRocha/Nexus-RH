import select
import asyncio
from models import TMPLIGMONI
from repositories.db_proc import get_db as get_db_proc

class CallDataService():

    def __init__(self):
        self.db = get_db_proc()

    def get_all_calls_async (self,valid_codes: list,invalid_codes:list):
        stmt = select(TMPLIGMONI).where(TMPLIGMONI.TP_PROCESSAMENTO )


        pass
    pass