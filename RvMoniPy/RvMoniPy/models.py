from typing import Optional

from sqlalchemy import BigInteger, DateTime, Integer, PrimaryKeyConstraint, String
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column
import datetime

class Base(DeclarativeBase):
    pass


class TMPLIGMONI(Base):
    __tablename__ = 'TMP_LIG_MONI'
    __table_args__ = (
        PrimaryKeyConstraint('CD_CLIENTE_ACAO', name='PK_TMP_LIG_MONI'),
    )

    CD_CLIENTE_ACAO: Mapped[int] = mapped_column(BigInteger, primary_key=True)
    DT_ACAO: Mapped[datetime.datetime] = mapped_column(DateTime)
    TP_PROCESSAMENTO: Mapped[int] = mapped_column(Integer)
    NR_CPF_OPERADOR: Mapped[Optional[int]] = mapped_column(BigInteger)
    NR_CPF_CLIENTE: Mapped[Optional[int]] = mapped_column(BigInteger)
    NR_CALLID: Mapped[Optional[str]] = mapped_column(String(50, 'Latin1_General_CI_AI'))
    NM_COLABORADOR: Mapped[Optional[str]] = mapped_column(String(300, 'Latin1_General_CI_AI'))
    SUPERVISOR: Mapped[Optional[str]] = mapped_column(String(300, 'Latin1_General_CI_AI'))
    RESULTADO_PROCESSAMENTO: Mapped[Optional[str]] = mapped_column(String(120, 'Latin1_General_CI_AI'))
    NM_CAMINHO: Mapped[Optional[str]] = mapped_column(String(400, 'Latin1_General_CI_AI'))
    NM_ARQUIVO: Mapped[Optional[str]] = mapped_column(String(200, 'Latin1_General_CI_AI'))
    NM_CAMINHO_INTERNO: Mapped[Optional[str]] = mapped_column(String(200, 'Latin1_General_CI_AI'))
