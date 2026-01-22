#!/bin/bash

# Authentication Testing Script for UCRS API
# This script tests all authentication endpoints

API_URL="http://localhost:8000/api"
CONTENT_TYPE="Content-Type: application/json"
ACCEPT="Accept: application/json"

echo "🚀 UCRS API Authentication Test Script"
echo "======================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test 1: Register
echo -e "${BLUE}1. Testing Registration...${NC}"
REGISTER_RESPONSE=$(curl -s -X POST "$API_URL/auth/register" \
  -H "$CONTENT_TYPE" \
  -H "$ACCEPT" \
  -d '{
    "name": "Test User",
    "email": "testuser@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }')

echo "$REGISTER_RESPONSE" | jq .

# Extract token from registration
TOKEN=$(echo "$REGISTER_RESPONSE" | jq -r '.access_token')

if [ "$TOKEN" != "null" ] && [ ! -z "$TOKEN" ]; then
    echo -e "${GREEN}✓ Registration successful!${NC}"
    echo "Token: $TOKEN"
else
    echo -e "${RED}✗ Registration failed${NC}"
fi

echo ""
echo "======================================"
echo ""

# Test 2: Login
echo -e "${BLUE}2. Testing Login...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "$CONTENT_TYPE" \
  -H "$ACCEPT" \
  -d '{
    "email": "testuser@example.com",
    "password": "password123"
  }')

echo "$LOGIN_RESPONSE" | jq .

# Extract token from login
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.access_token')

if [ "$TOKEN" != "null" ] && [ ! -z "$TOKEN" ]; then
    echo -e "${GREEN}✓ Login successful!${NC}"
    echo "Token: $TOKEN"
else
    echo -e "${RED}✗ Login failed${NC}"
    exit 1
fi

echo ""
echo "======================================"
echo ""

# Test 3: Get Current User
echo -e "${BLUE}3. Testing Get Current User (Protected)...${NC}"
ME_RESPONSE=$(curl -s -X GET "$API_URL/auth/me" \
  -H "Authorization: Bearer $TOKEN" \
  -H "$ACCEPT")

echo "$ME_RESPONSE" | jq .

if echo "$ME_RESPONSE" | jq -e '.id' > /dev/null; then
    echo -e "${GREEN}✓ Protected endpoint accessible!${NC}"
else
    echo -e "${RED}✗ Protected endpoint failed${NC}"
fi

echo ""
echo "======================================"
echo ""

# Test 4: Access Another Protected Endpoint
echo -e "${BLUE}4. Testing /api/user (Protected)...${NC}"
USER_RESPONSE=$(curl -s -X GET "$API_URL/user" \
  -H "Authorization: Bearer $TOKEN" \
  -H "$ACCEPT")

echo "$USER_RESPONSE" | jq .

if echo "$USER_RESPONSE" | jq -e '.id' > /dev/null; then
    echo -e "${GREEN}✓ /api/user endpoint accessible!${NC}"
else
    echo -e "${RED}✗ /api/user endpoint failed${NC}"
fi

echo ""
echo "======================================"
echo ""

# Test 5: Logout
echo -e "${BLUE}5. Testing Logout...${NC}"
LOGOUT_RESPONSE=$(curl -s -X POST "$API_URL/auth/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "$ACCEPT")

echo "$LOGOUT_RESPONSE" | jq .

if echo "$LOGOUT_RESPONSE" | jq -e '.message' | grep -q "Logged out successfully"; then
    echo -e "${GREEN}✓ Logout successful!${NC}"
else
    echo -e "${RED}✗ Logout failed${NC}"
fi

echo ""
echo "======================================"
echo ""

# Test 6: Try Accessing Protected Endpoint After Logout
echo -e "${BLUE}6. Testing Protected Endpoint After Logout (Should Fail)...${NC}"
AFTER_LOGOUT=$(curl -s -X GET "$API_URL/auth/me" \
  -H "Authorization: Bearer $TOKEN" \
  -H "$ACCEPT")

echo "$AFTER_LOGOUT" | jq .

if echo "$AFTER_LOGOUT" | jq -e '.message' | grep -q "Unauthenticated"; then
    echo -e "${GREEN}✓ Token properly revoked!${NC}"
else
    echo -e "${RED}✗ Token still valid (should be revoked)${NC}"
fi

echo ""
echo "======================================"
echo -e "${GREEN}✅ All tests completed!${NC}"
echo "======================================"
