-- MySQL dump 10.13  Distrib 5.6.33, for Linux (x86_64)
--
-- Host: localhost    Database: c0310458_sgi
-- ------------------------------------------------------
-- Server version	5.6.31-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `asistenciaeraup`
--

DROP TABLE IF EXISTS `asistenciaeraup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `asistenciaeraup` (
  `idAsistenciaERAUP` int(11) NOT NULL AUTO_INCREMENT,
  `idDistrito` int(11) NOT NULL,
  `idEvento` int(11) NOT NULL,
  `CupoReservado` int(11) NOT NULL,
  `Inscriptos` int(11) NOT NULL,
  PRIMARY KEY (`idAsistenciaERAUP`),
  KEY `fkIdDistrito` (`idDistrito`),
  KEY `fkIdEvento` (`idEvento`)
) ENGINE=MyISAM AUTO_INCREMENT=94 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calidadasistenciaevento`
--

DROP TABLE IF EXISTS `calidadasistenciaevento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `calidadasistenciaevento` (
  `idCalidadAsistencia` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  PRIMARY KEY (`idCalidadAsistencia`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cargoairaup`
--

DROP TABLE IF EXISTS `cargoairaup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `cargoairaup` (
  `idCargoAIRAUP` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  PRIMARY KEY (`idCargoAIRAUP`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cargoclub`
--

DROP TABLE IF EXISTS `cargoclub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `cargoclub` (
  `idCargoClub` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  PRIMARY KEY (`idCargoClub`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cargodistrito`
--

DROP TABLE IF EXISTS `cargodistrito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `cargodistrito` (
  `idCargoDistrito` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  PRIMARY KEY (`idCargoDistrito`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `club`
--

DROP TABLE IF EXISTS `club`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `club` (
  `idClub` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `idDistrito` int(11) NOT NULL,
  `Activo` tinyint(1) NOT NULL,
  PRIMARY KEY (`idClub`),
  KEY `fkDistrito_idx` (`idDistrito`),
  CONSTRAINT `fkDistrito` FOREIGN KEY (`idDistrito`) REFERENCES `distrito` (`idDistrito`) ON DELETE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=355 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `datosmedicos`
--

DROP TABLE IF EXISTS `datosmedicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `datosmedicos` (
  `idDatosMedicos` int(11) NOT NULL AUTO_INCREMENT,
  `idSocio` int(11) NOT NULL,
  `ObraSocial` varchar(45) NOT NULL,
  `NumeroSocio` varchar(45) DEFAULT NULL,
  `GrupoSangre` varchar(45) DEFAULT NULL,
  `Factor` varchar(45) DEFAULT NULL,
  `EnfermedadCronica` tinyint(1) NOT NULL,
  `EnfermedadCronicaE` varchar(45) DEFAULT NULL,
  `Internacion3anos` tinyint(1) NOT NULL,
  `Internacion3anosE` varchar(45) DEFAULT NULL,
  `EnfermedadInfecciosa` tinyint(1) NOT NULL,
  `EnfermedadInfecciosaE` varchar(45) DEFAULT NULL,
  `IntervencionQuirurjica` tinyint(1) NOT NULL,
  `IntervencionQuirurjicaE` varchar(45) DEFAULT NULL,
  `Alergia` tinyint(1) NOT NULL,
  `AlergiaE` varchar(45) DEFAULT NULL,
  `Vegetariano` tinyint(1) NOT NULL,
  `Dieta` varchar(200) DEFAULT NULL,
  `Fuma` tinyint(1) NOT NULL,
  `Lateralidad` tinyint(1) NOT NULL,
  `Lentes` tinyint(1) NOT NULL,
  `Audifonos` tinyint(1) NOT NULL,
  `LimitacionFisica` tinyint(1) NOT NULL,
  `LimitacionFisicaE` varchar(45) DEFAULT NULL,
  `DonanteOrganos` tinyint(4) DEFAULT NULL,
  `DonanteMedula` tinyint(4) DEFAULT NULL,
  `NombreMedicamento` varchar(45) DEFAULT NULL,
  `Droga` varchar(45) DEFAULT NULL,
  `CantidadMedicamento` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idDatosMedicos`),
  UNIQUE KEY `idSocio` (`idSocio`),
  KEY `fkSocio_idx` (`idSocio`),
  CONSTRAINT `fkSocioDatosMedicos` FOREIGN KEY (`idSocio`) REFERENCES `socio` (`idSocio`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3426 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `distrito`
--

DROP TABLE IF EXISTS `distrito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `distrito` (
  `idDistrito` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  PRIMARY KEY (`idDistrito`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `evento`
--

DROP TABLE IF EXISTS `evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `evento` (
  `idEvento` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) NOT NULL,
  `FechaInicio` datetime NOT NULL,
  `FechaFin` datetime NOT NULL,
  `FechaInicioInscripcion` datetime NOT NULL,
  `FechaFinInscripcion` datetime NOT NULL,
  `FechaInicioInscripcion2` datetime NOT NULL,
  `FechaFinInscripcion2` datetime NOT NULL,
  `PorcentajeRotarios1` int(11) NOT NULL,
  `PorcentajeRotarios2` int(11) NOT NULL,
  `PorcentajeExtranjeros1` int(11) NOT NULL,
  `PorcentajeExtranjeros2` int(11) NOT NULL,
  `Reserva` int(11) NOT NULL,
  `Ubicacion` varchar(100) NOT NULL,
  `Costo` double NOT NULL,
  `idMoneda` int(11) NOT NULL,
  `idTipoEvento` int(11) NOT NULL,
  `CupoMaximo` int(11) NOT NULL,
  `Habilitado` tinyint(1) NOT NULL,
  PRIMARY KEY (`idEvento`),
  KEY `fkEventoTipoEvento_idx` (`idTipoEvento`),
  KEY `fkIdMonedaEvento` (`idMoneda`),
  CONSTRAINT `fkEventoTipoEvento` FOREIGN KEY (`idTipoEvento`) REFERENCES `tipoevento` (`idTipoEvento`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fkIdMonedaEvento` FOREIGN KEY (`idMoneda`) REFERENCES `moneda` (`idMoneda`)
) ENGINE=InnoDB AUTO_INCREMENT=7059 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `eventoadmin`
--

DROP TABLE IF EXISTS `eventoadmin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `eventoadmin` (
  `idEventoAdmin` int(11) NOT NULL AUTO_INCREMENT,
  `idEvento` int(11) NOT NULL,
  `idSocio` int(11) NOT NULL,
  PRIMARY KEY (`idEventoAdmin`),
  UNIQUE KEY `unicidadSocioEvento` (`idEvento`,`idSocio`),
  KEY `fkEventoAdminSocio` (`idSocio`)
) ENGINE=MyISAM AUTO_INCREMENT=1470 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `eventodistrito`
--

DROP TABLE IF EXISTS `eventodistrito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `eventodistrito` (
  `idEventoDistrito` int(11) NOT NULL AUTO_INCREMENT,
  `idEvento` int(11) NOT NULL,
  `idDistrito` int(11) NOT NULL,
  PRIMARY KEY (`idEventoDistrito`),
  KEY `fkEventoDistritoEvento` (`idEvento`),
  KEY `fkEventoDistritoDistrito` (`idDistrito`)
) ENGINE=MyISAM AUTO_INCREMENT=898 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historialcargoairaup`
--

DROP TABLE IF EXISTS `historialcargoairaup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `historialcargoairaup` (
  `idHistorialCargoAIRAUP` int(11) NOT NULL AUTO_INCREMENT,
  `idSocio` int(11) NOT NULL,
  `idCargoAIRAUP` int(11) NOT NULL,
  `idPeriodo` int(11) NOT NULL,
  PRIMARY KEY (`idHistorialCargoAIRAUP`,`idSocio`,`idCargoAIRAUP`,`idPeriodo`),
  KEY `fkSocioCargo_idx` (`idSocio`),
  KEY `fkPeriodoCargoAIRAUP_idx` (`idPeriodo`),
  KEY `fkCargoAIRAUPidCargoAIRAUP` (`idCargoAIRAUP`),
  CONSTRAINT `fkCargoAIRAUPidCargoAIRAUP` FOREIGN KEY (`idCargoAIRAUP`) REFERENCES `cargoairaup` (`idCargoAIRAUP`),
  CONSTRAINT `fkPeriodoCargoAIRAUP` FOREIGN KEY (`idPeriodo`) REFERENCES `periodo` (`idPeriodo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fkSocioCargoAIRAUP` FOREIGN KEY (`idSocio`) REFERENCES `socio` (`idSocio`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=557 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historialcargoclub`
--

DROP TABLE IF EXISTS `historialcargoclub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `historialcargoclub` (
  `idHistorialCargoClub` int(11) NOT NULL AUTO_INCREMENT,
  `idSocio` int(11) NOT NULL,
  `idCargoClub` int(11) NOT NULL,
  `idPeriodo` int(11) NOT NULL,
  PRIMARY KEY (`idHistorialCargoClub`,`idSocio`,`idCargoClub`,`idPeriodo`),
  KEY `fkIdSocioCargoClub_idx` (`idSocio`),
  KEY `fkPeriodoCargoClub_idx` (`idPeriodo`),
  KEY `fkCargoClubidCargoClub` (`idCargoClub`),
  CONSTRAINT `fkCargoClubidCargoClub` FOREIGN KEY (`idCargoClub`) REFERENCES `cargoclub` (`idCargoClub`),
  CONSTRAINT `fkIdSocioCargoClub` FOREIGN KEY (`idSocio`) REFERENCES `socio` (`idSocio`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fkPeriodoCargoClub` FOREIGN KEY (`idPeriodo`) REFERENCES `periodo` (`idPeriodo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=18580 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historialcargodistrito`
--

DROP TABLE IF EXISTS `historialcargodistrito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `historialcargodistrito` (
  `idHistorialCargoDistrito` int(11) NOT NULL AUTO_INCREMENT,
  `idSocio` int(11) NOT NULL,
  `idCargoDistrito` int(11) NOT NULL,
  `idPeriodo` int(11) NOT NULL,
  PRIMARY KEY (`idHistorialCargoDistrito`,`idSocio`,`idCargoDistrito`,`idPeriodo`),
  KEY `fkSocioCargoDistrito_idx` (`idSocio`),
  KEY `fkPeriodoCargoDistrito_idx` (`idPeriodo`),
  KEY `fkCargoDistritoidCargoDistrito` (`idCargoDistrito`),
  CONSTRAINT `fkCargoDistritoidCargoDistrito` FOREIGN KEY (`idCargoDistrito`) REFERENCES `cargodistrito` (`idCargoDistrito`),
  CONSTRAINT `fkPeriodoCargoDistrito` FOREIGN KEY (`idPeriodo`) REFERENCES `periodo` (`idPeriodo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fkSocioCargoDistrito` FOREIGN KEY (`idSocio`) REFERENCES `socio` (`idSocio`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2068 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historialevento`
--

DROP TABLE IF EXISTS `historialevento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `historialevento` (
  `idHistorialEvento` int(11) NOT NULL AUTO_INCREMENT,
  `idSocio` int(11) NOT NULL,
  `idTipoEvento` int(11) NOT NULL,
  `CantidadAsistencias` int(11) NOT NULL,
  `VecesInstructor` int(11) NOT NULL,
  PRIMARY KEY (`idHistorialEvento`),
  UNIQUE KEY `unicidadPorEvento` (`idSocio`,`idTipoEvento`),
  KEY `fkSocioHistorialEvento_idx` (`idSocio`),
  KEY `fkTipoEventoHistorialEvento_idx` (`idTipoEvento`)
) ENGINE=InnoDB AUTO_INCREMENT=84796 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historialinscripcion`
--

DROP TABLE IF EXISTS `historialinscripcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `historialinscripcion` (
  `idHistorialInscripcion` int(11) NOT NULL AUTO_INCREMENT,
  `idSocio` int(11) NOT NULL,
  `idEvento` int(11) NOT NULL,
  `CalidadAsistencia` varchar(45) NOT NULL,
  PRIMARY KEY (`idHistorialInscripcion`),
  UNIQUE KEY `unicidadPorSocio` (`idSocio`,`idEvento`),
  KEY `fkSocio_idx` (`idSocio`),
  KEY `fkEvento_idx` (`idEvento`),
  CONSTRAINT `fkEvento` FOREIGN KEY (`idEvento`) REFERENCES `evento` (`idEvento`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fkSocioInscripcion` FOREIGN KEY (`idSocio`) REFERENCES `socio` (`idSocio`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=10035 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historialmesaeraup`
--

DROP TABLE IF EXISTS `historialmesaeraup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `historialmesaeraup` (
  `idHistorialMesaEraup` int(11) NOT NULL AUTO_INCREMENT,
  `Mesa` varchar(100) NOT NULL,
  `idSocio` int(11) NOT NULL,
  `Instructor` tinyint(4) NOT NULL,
  PRIMARY KEY (`idHistorialMesaEraup`),
  KEY `fkSocioMesaERAUP_idx` (`idSocio`),
  CONSTRAINT `fkSocioMesaERAUP` FOREIGN KEY (`idSocio`) REFERENCES `socio` (`idSocio`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=5389 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inscripcionevento`
--

DROP TABLE IF EXISTS `inscripcionevento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `inscripcionevento` (
  `idInscripcion` int(11) NOT NULL AUTO_INCREMENT,
  `idSocio` int(11) NOT NULL,
  `idCalidadAsistencia` int(11) NOT NULL,
  `idEvento` int(11) NOT NULL,
  `idTransporteEvento` int(11) NOT NULL,
  `FechaInscripcion` datetime NOT NULL,
  `Aprobado` tinyint(1) DEFAULT NULL,
  `Observaciones` varchar(8000) DEFAULT NULL,
  `Monto` double NOT NULL,
  `idMoneda` int(11) NOT NULL,
  `Cotizacion` double NOT NULL,
  `FechaPago` datetime NOT NULL,
  `Reserva` tinyint(4) NOT NULL,
  `Eliminado` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`idInscripcion`),
  UNIQUE KEY `unicidadPorSocio` (`idSocio`,`idEvento`),
  KEY `fkSocio_idx` (`idSocio`),
  KEY `fkSocioCalidadAsistencia_idx` (`idCalidadAsistencia`),
  KEY `fkInscripcionEvento_idx` (`idEvento`),
  CONSTRAINT `fkInscripcionEvento` FOREIGN KEY (`idEvento`) REFERENCES `evento` (`idEvento`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fkSocioAsamblea` FOREIGN KEY (`idSocio`) REFERENCES `socio` (`idSocio`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fkSocioCalidadAsistenciaAsamblea` FOREIGN KEY (`idCalidadAsistencia`) REFERENCES `calidadasistenciaevento` (`idCalidadAsistencia`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=11452 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inscripcionservicioeraup`
--

DROP TABLE IF EXISTS `inscripcionservicioeraup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `inscripcionservicioeraup` (
  `idInscripcionServicioERAUP` int(11) NOT NULL AUTO_INCREMENT,
  `idEvento` int(11) NOT NULL,
  `idSocio` int(11) NOT NULL,
  `idServicioERAUP` int(11) NOT NULL,
  `Prioridad` int(11) NOT NULL,
  PRIMARY KEY (`idInscripcionServicioERAUP`),
  KEY `fkidServicioERAUP` (`idServicioERAUP`),
  KEY `fkidSocioServicioERAUP` (`idSocio`),
  KEY `fkidEventoServicioERAUP` (`idEvento`)
) ENGINE=MyISAM AUTO_INCREMENT=4972 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `moneda`
--

DROP TABLE IF EXISTS `moneda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `moneda` (
  `idMoneda` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`idMoneda`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `periodo`
--

DROP TABLE IF EXISTS `periodo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `periodo` (
  `idPeriodo` int(11) NOT NULL AUTO_INCREMENT,
  `AnoInicio` int(11) NOT NULL,
  `AnoFin` int(11) NOT NULL,
  PRIMARY KEY (`idPeriodo`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `preregistro`
--

DROP TABLE IF EXISTS `preregistro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `preregistro` (
  `idPreRegistro` int(11) NOT NULL AUTO_INCREMENT,
  `Nombres` varchar(45) NOT NULL,
  `Apellidos` varchar(45) NOT NULL,
  `Documento` int(20) DEFAULT NULL,
  `Direccion` varchar(150) DEFAULT NULL,
  `Ciudad` varchar(150) DEFAULT NULL,
  `ViveCon` varchar(150) DEFAULT NULL,
  `Hospeda` tinyint(4) DEFAULT NULL,
  `FechaNac` date NOT NULL,
  `Sexo` tinyint(4) DEFAULT NULL,
  `Email` varchar(60) NOT NULL,
  `Password` varchar(45) NOT NULL,
  `Telefono` varchar(45) NOT NULL,
  `AreaEstudio` varchar(150) NOT NULL,
  `Trabajo` varchar(150) DEFAULT NULL,
  `Facebook` varchar(150) DEFAULT NULL,
  `FechaRegistro` datetime NOT NULL,
  `idTransaccion` int(11) NOT NULL,
  PRIMARY KEY (`idPreRegistro`)
) ENGINE=InnoDB AUTO_INCREMENT=4567 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `servicioeraup`
--

DROP TABLE IF EXISTS `servicioeraup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `servicioeraup` (
  `idServicioERAUP` int(11) NOT NULL AUTO_INCREMENT,
  `idEvento` int(11) NOT NULL,
  `Nombre` varchar(8000) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`idServicioERAUP`),
  KEY `fkidEvento` (`idEvento`)
) ENGINE=MyISAM AUTO_INCREMENT=48 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `socio`
--

DROP TABLE IF EXISTS `socio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `socio` (
  `idSocio` int(11) NOT NULL AUTO_INCREMENT,
  `Nombres` varchar(45) NOT NULL,
  `Apellidos` varchar(45) NOT NULL,
  `Documento` int(20) DEFAULT NULL,
  `Direccion` varchar(150) DEFAULT NULL,
  `Ciudad` varchar(150) DEFAULT NULL,
  `FechaNac` date NOT NULL,
  `Sexo` int(11) DEFAULT NULL,
  `Email` varchar(60) NOT NULL,
  `Password` varchar(45) NOT NULL,
  `Facebook` varchar(150) DEFAULT NULL,
  `Telefono` varchar(45) NOT NULL,
  `ViveCon` varchar(150) DEFAULT NULL,
  `Hospeda` int(11) DEFAULT NULL,
  `idClub` int(11) NOT NULL,
  `FechaIngreso` date NOT NULL,
  `idTipoRueda` int(11) NOT NULL,
  `AreaEstudio` varchar(150) NOT NULL,
  `Trabajo` varchar(150) DEFAULT NULL,
  `NombreContacto` varchar(45) NOT NULL,
  `TelefonoContacto` varchar(45) NOT NULL,
  `RelacionContacto` varchar(45) NOT NULL,
  `Admin` tinyint(1) NOT NULL,
  `Activo` tinyint(1) NOT NULL,
  `FechaRegistro` datetime DEFAULT NULL,
  PRIMARY KEY (`idSocio`),
  UNIQUE KEY `Email` (`Email`),
  KEY `fkClub_idx` (`idClub`),
  KEY `fkRueda_idx` (`idTipoRueda`),
  KEY `Apellidos` (`Apellidos`),
  CONSTRAINT `fkRueda` FOREIGN KEY (`idTipoRueda`) REFERENCES `tiporueda` (`idTipoRueda`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3483 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipoevento`
--

DROP TABLE IF EXISTS `tipoevento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `tipoevento` (
  `idTipoEvento` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  `Tipo` tinyint(4) NOT NULL COMMENT 'Establece si el evento es distrital (0) o multidistrital (1) para definir quien debe aprobar la inscripción del socio',
  PRIMARY KEY (`idTipoEvento`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tiporueda`
--

DROP TABLE IF EXISTS `tiporueda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `tiporueda` (
  `idTipoRueda` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  PRIMARY KEY (`idTipoRueda`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transporteevento`
--

DROP TABLE IF EXISTS `transporteevento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `transporteevento` (
  `idTransporteEvento` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(45) NOT NULL,
  `Costo` double NOT NULL,
  `idMoneda` int(11) NOT NULL,
  `idEvento` int(11) NOT NULL,
  PRIMARY KEY (`idTransporteEvento`),
  KEY `fkEventoTransporteEvento_idx` (`idEvento`),
  KEY `fkIdMonedaTransporte` (`idMoneda`),
  CONSTRAINT `fkEventoTransporteEvento` FOREIGN KEY (`idEvento`) REFERENCES `evento` (`idEvento`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fkIdMonedaTransporte` FOREIGN KEY (`idMoneda`) REFERENCES `moneda` (`idMoneda`)
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-03 20:25:43
