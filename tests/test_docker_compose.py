import unittest
import re

class DockerComposeTest(unittest.TestCase):
    def test_services_exist(self):
        with open('docker-compose.yml') as f:
            text = f.read()
        pattern_app = re.compile(r'^\s*app:', re.MULTILINE)
        pattern_web = re.compile(r'^\s*web:', re.MULTILINE)
        pattern_db = re.compile(r'^\s*db:', re.MULTILINE)
        pattern_node = re.compile(r'^\s*node:', re.MULTILINE)
        self.assertRegex(text, pattern_app)
        self.assertRegex(text, pattern_web)
        self.assertRegex(text, pattern_db)
        self.assertRegex(text, pattern_node)

if __name__ == '__main__':
    unittest.main()
