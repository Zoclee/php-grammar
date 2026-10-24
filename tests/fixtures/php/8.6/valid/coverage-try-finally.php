<?php
try { f(); } catch (A|B $e) { throw $e; } finally { cleanup(); }
