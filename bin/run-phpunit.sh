#!/bin/bash

# Run PHPUnit test

# https://docs.phpunit.de/en/8.5/installation.html#recommended-php-configuration

# Ejecuta pruebas de los test en la carpeta de test unitarios
# vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/Unit

# Ejecuta las pruebas definidas para el API
htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php --testsuite api

# Ejecuta pruebas del test indicado
# NOTA: usar de forma manual para agilizar las pruebas concretas
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ArticulosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/AgentesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/AgentesExtAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/AlbaranesClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/AlbaranesProveedoresAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ArticulosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosCargosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosCargosCandidatosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosDetallesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosTagsAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosTagsCandidatosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosValoracionesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosZonasAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ContactosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ContactosClienteAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ContactosInformacionesAdicionalesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ContactosInteresesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmCalendariosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmContactosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmEstadosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmFuentesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmFuentesCostesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmInformacionesAdicionalesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmInteresesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmNotasAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmOportunidadesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmRecurredPlansAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmTiposNotasAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/EstadosServiciosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ExpedientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/FabricantesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/FacturasClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/FacturasProveedoresAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/FamiliasAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasFacturasClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasFacturasProveedoresAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasPedidosClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasPedidosProveedoresAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasPresupuestosClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasServiciosClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/PedidosClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/PedidosProveedoresAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/PowerbiAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/PresupuestosClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ProveedoresAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/RecibosClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/RecibosProveedoresAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ServiciosAgentesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ServiciosClientesAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ServiciosClientesTagsAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TagsAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TareasServiciosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TareasServiciosTagsAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TiposPuestosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TiposServiciosAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/VisitasAPITest.php
#htdocs/vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ZonasAPITest.php
