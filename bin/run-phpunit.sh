#!/bin/bash

# Run PHPUnit test

# https://docs.phpunit.de/en/8.5/installation.html#recommended-php-configuration

# Ejecuta pruebas de los test en la carpeta de test unitarios
# vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/Unit

# Ejecuta las pruebas definidas para el API
vendor/bin/phpunit --bootstrap Test/BootStrap.php --testsuite api

# Ejecuta pruebas del test indicado
# NOTA: usar de forma manual para agilizar las pruebas concretas
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ArticulosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/AgentesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/AgentesExtAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/AlbaranesClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/AlbaranesProveedoresAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ArticulosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosCargosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosCargosCandidatosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosDetallesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosTagsAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosTagsCandidatosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosValoracionesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CandidatosZonasAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ContactosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ContactosClienteAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ContactosInformacionesAdicionalesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ContactosInteresesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmCalendariosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmContactosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmEstadosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmFuentesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmFuentesCostesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmInformacionesAdicionalesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmInteresesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmNotasAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmOportunidadesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmRecurredPlansAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/CrmTiposNotasAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/EstadosServiciosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ExpedientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/FabricantesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/FacturasClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/FacturasProveedoresAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/FamiliasAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasFacturasClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasFacturasProveedoresAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasPedidosClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasPedidosProveedoresAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasPresupuestosClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/LineasServiciosClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/PedidosClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/PedidosProveedoresAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/PowerbiAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/PresupuestosClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ProveedoresAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/RecibosClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/RecibosProveedoresAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ServiciosAgentesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ServiciosClientesAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ServiciosClientesTagsAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TagsAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TareasServiciosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TareasServiciosTagsAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TiposPuestosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/TiposServiciosAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/VisitasAPITest.php
#vendor/bin/phpunit --bootstrap Test/BootStrap.php Test/API/ZonasAPITest.php
